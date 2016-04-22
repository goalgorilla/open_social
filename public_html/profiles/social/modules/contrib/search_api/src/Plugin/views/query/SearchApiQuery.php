<?php

namespace Drupal\search_api\Plugin\views\query;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Query\ConditionGroupInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api\UncacheableDependencyTrait;
use Drupal\search_api\Utility;
use Drupal\user\Entity\User;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a Views query class for searching on Search API indexes.
 *
 * @ViewsQuery(
 *   id = "search_api_query",
 *   title = @Translation("Search API Query"),
 *   help = @Translation("The query will be generated and run using the Search API.")
 * )
 */
class SearchApiQuery extends QueryPluginBase {

  use UncacheableDependencyTrait;

  /**
   * Number of results to display.
   *
   * @var int
   */
  protected $limit;

  /**
   * Offset of first displayed result.
   *
   * @var int
   */
  protected $offset;

  /**
   * The index this view accesses.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * The query that will be executed.
   *
   * @var \Drupal\search_api\Query\QueryInterface
   */
  protected $query;

  /**
   * The results returned by the query, after it was executed.
   *
   * @var \Drupal\search_api\Query\ResultSetInterface
   */
  protected $searchApiResults;

  /**
   * Array of all encountered errors.
   *
   * Each of these is fatal, meaning that a non-empty $errors property will
   * result in an empty result being returned.
   *
   * @var array
   */
  protected $errors = array();

  /**
   * Whether to abort the search instead of executing it.
   *
   * @var bool
   */
  protected $abort = FALSE;

  /**
   * The properties that should be retrieved from result items.
   *
   * The array is keyed by datasource ID (which might be NULL) and property
   * path, the values are the associated combined property paths.
   *
   * @var string[][]
   */
  protected $retrievedProperties = array();

  /**
   * The query's conditions representing the different Views filter groups.
   *
   * @var array
   */
  protected $conditions = array();

  /**
   * The conjunction with which multiple filter groups are combined.
   *
   * @var string
   */
  protected $groupOperator = 'AND';

  /**
   * The logger to use for log messages.
   *
   * @var \Psr\Log\LoggerInterface|null
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $plugin */
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    /** @var \Psr\Log\LoggerInterface $logger */
    $logger = $container->get('logger.factory')->get('search_api');
    $plugin->setLogger($logger);

    return $plugin;
  }

  /**
   * Retrieves the logger to use for log messages.
   *
   * @return \Psr\Log\LoggerInterface
   *   The logger to use.
   */
  public function getLogger() {
    return $this->logger ?: \Drupal::logger('search_api');
  }

  /**
   * Sets the logger to use for log messages.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The new logger.
   *
   * @return $this
   */
  public function setLogger(LoggerInterface $logger) {
    $this->logger = $logger;
    return $this;
  }

  /**
   * Loads the search index belonging to the given Views base table.
   *
   * @param string $table
   *   The Views base table ID.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   (optional) The entity type manager to use.
   *
   * @return \Drupal\search_api\IndexInterface|null
   *   The requested search index, or NULL if it could not be found and loaded.
   */
  public static function getIndexFromTable($table, EntityTypeManagerInterface $entity_type_manager = NULL) {
    // @todo Instead use Views::viewsData() – injected, too – to load the base
    //   table definition and use the "index" (or maybe rename to
    //   "search_api_index") field from there.
    if (substr($table, 0, 17) == 'search_api_index_') {
      $index_id = substr($table, 17);
      if ($entity_type_manager) {
        return $entity_type_manager->getStorage('search_api_index')->load($index_id);
      }
      return Index::load($index_id);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    try {
      parent::init($view, $display, $options);
      $this->index = static::getIndexFromTable($view->storage->get('base_table'));
      if (!$this->index) {
        $this->abort(new FormattableMarkup('View %view is not based on Search API but tries to use its query plugin.', array('%view' => $view->storage->label())));
      }
      $this->retrievedProperties = array_fill_keys($this->index->getDatasourceIds(), array());
      $this->retrievedProperties[NULL] = array();
      $this->query = $this->index->query();
      $this->query->setParseMode($this->options['parse_mode']);
      $this->query->addTag('views');
      $this->query->addTag('views_' . $view->id());
      $this->query->setOption('search_api_view', $view);

    }
    catch (\Exception $e) {
      $this->abort($e->getMessage());
    }
  }

  /**
   * Adds a property to be retrieved.
   *
   * Currently doesn't serve any purpose, but might be added to the search query
   * in the future to help backends that support returning fields determine
   * which of the fields should actually be returned.
   *
   * @param string $combined_property_path
   *   The combined property path of the property that should be retrieved.
   *
   * @return $this
   */
  public function addRetrievedProperty($combined_property_path) {
    list($datasource_id, $property_path) = Utility::splitCombinedId($combined_property_path);
    $this->retrievedProperties[$datasource_id][$property_path] = $combined_property_path;
    return $this;
  }

  /**
   * Adds a field to the table.
   *
   * This replicates the interface of Views' default SQL backend to simplify
   * the Views integration of the Search API. If you are writing Search
   * API-specific Views code, you should better use the addRetrievedProperty()
   * method.
   *
   * @param string|null $table
   *   Ignored.
   * @param string $field
   *   The combined property path of the property that should be retrieved.
   * @param string $alias
   *   (optional) Ignored.
   * @param array $params
   *   (optional) Ignored.
   *
   * @return string
   *   The name that this field can be referred to as (always $field).
   *
   * @see \Drupal\views\Plugin\views\query\Sql::addField()
   * @see \Drupal\search_api\Plugin\views\query\SearchApiQuery::addField()
   */
  public function addField($table, $field, $alias = '', $params = array()) {
    $this->addRetrievedProperty($field);
    return $field;
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    return parent::defineOptions() + array(
      'bypass_access' => array(
        'default' => FALSE,
      ),
      'skip_access' => array(
        'default' => FALSE,
      ),
      'parse_mode' => array(
        'default' => 'terms',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['bypass_access'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Bypass access checks'),
      '#description' => $this->t('If the underlying search index has access checks enabled (e.g., through the "Content access" processor), this option allows you to disable them for this view. This will never disable any filters placed on this view.'),
      '#default_value' => $this->options['bypass_access'],
    );

    if ($this->getEntityTypes(TRUE)) {
      $form['skip_access'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Skip entity access checks'),
        '#description' => $this->t("By default, an additional access check will be executed for each entity returned by the search query. However, since removing results this way will break paging and result counts, it is preferable to configure the view in a way that it will only return accessible results. If you are sure that only accessible results will be returned in the search, or if you want to show results to which the user normally wouldn't have access, you can enable this option to skip those additional access checks. This should be used with care."),
        '#default_value' => $this->options['skip_access'],
        '#weight' => -1,
      );
      $form['bypass_access']['#states']['visible'][':input[name="query[options][skip_access]"]']['checked'] = TRUE;
    }

    // @todo Move this setting to the argument and filter plugins where it makes
    //   more sense for users.
    $form['parse_mode'] = array(
      '#type' => 'select',
      '#title' => $this->t('Parse mode'),
      '#description' => $this->t('Choose how the search keys will be parsed.'),
      '#options' => array(),
      '#default_value' => $this->options['parse_mode'],
    );
    foreach ($this->query->parseModes() as $key => $mode) {
      $form['parse_mode']['#options'][$key] = $mode['name'];
      if (!empty($mode['description'])) {
        $states['visible'][':input[name="query[options][parse_mode]"]']['value'] = $key;
        $form["parse_mode_{$key}_description"] = array(
          '#type' => 'item',
          '#title' => $mode['name'],
          '#description' => $mode['description'],
          '#states' => $states,
        );
      }
    }
  }

  /**
   * Checks for entity types contained in the current view's index.
   *
   * @param bool $return_bool
   *   (optional) If TRUE, returns a boolean instead of a list of datasources.
   *
   * @return string[]|bool
   *   If $return_bool is FALSE, an associative array mapping all datasources
   *   containing entities to their entity types. Otherwise, TRUE if there is at
   *   least one such datasource.
   */
  // @todo Might be useful enough to be moved to the Index class? Or maybe
  //   Utility, to finally stop the growth of the Index class.
  public function getEntityTypes($return_bool = FALSE) {
    $types = array();
    foreach ($this->index->getDatasources() as $datasource_id => $datasource) {
      if ($type = $datasource->getEntityTypeId()) {
        if ($return_bool) {
          return TRUE;
        }
        $types[$datasource_id] = $type;
      }
    }
    return $return_bool ? FALSE : $types;
  }

  /**
   * {@inheritdoc}
   */
  public function build(ViewExecutable $view) {
    $this->view = $view;

    if ($this->shouldAbort()) {
      return;
    }

    // Setup the nested filter structure for this query.
    if (!empty($this->conditions)) {
      // If the different groups are combined with the OR operator, we have to
      // add a new OR filter to the query to which the filters for the groups
      // will be added.
      if ($this->groupOperator === 'OR') {
        $base = $this->query->createConditionGroup('OR');
        $this->query->addConditionGroup($base);
      }
      else {
        $base = $this->query;
      }
      // Add a nested filter for each filter group, with its set conjunction.
      foreach ($this->conditions as $group_id => $group) {
        if (!empty($group['conditions']) || !empty($group['condition_groups'])) {
          $group += array('type' => 'AND');
          // For filters without a group, we want to always add them directly to
          // the query.
          $conditions = ($group_id === '') ? $this->query : $this->query->createConditionGroup($group['type']);
          if (!empty($group['conditions'])) {
            foreach ($group['conditions'] as $condition) {
              list($field, $value, $operator) = $condition;
              $conditions->addCondition($field, $value, $operator);
            }
          }
          if (!empty($group['condition_groups'])) {
            foreach ($group['condition_groups'] as $nested_conditions) {
              $conditions->addConditionGroup($nested_conditions);
            }
          }
          // If no group was given, the filters were already set on the query.
          if ($group_id !== '') {
            $base->addConditionGroup($conditions);
          }
        }
      }
    }

    // Initialize the pager and let it modify the query to add limits.
    $view->initPager();
    $view->pager->query();

    // Set the search ID, if it was not already set.
    if ($this->query->getOption('search id') == get_class($this->query)) {
      $this->query->setOption('search id', 'search_api_views:' . $view->storage->id() . ':' . $view->current_display);
    }

    // Add the "search_api_bypass_access" option to the query, if desired.
    if (!empty($this->options['bypass_access'])) {
      $this->query->setOption('search_api_bypass_access', TRUE);
    }

    // If the View and the Panel conspire to provide an overridden path then
    // pass that through as the base path.
    if (($path = $this->view->getPath()) && strpos(Url::fromRoute('<current>')->toString(), $this->view->override_path) !== 0) {
      $this->query->setOption('search_api_base_path', $path);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ViewExecutable $view) {
    \Drupal::moduleHandler()->invokeAll('views_query_alter', array($view, $this));
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ViewExecutable $view) {
    if ($this->shouldAbort()) {
      if (error_displayable()) {
        foreach ($this->errors as $msg) {
          drupal_set_message(Html::escape($msg), 'error');
        }
      }
      $view->result = array();
      $view->total_rows = 0;
      $view->execute_time = 0;
      return;
    }

    // Calculate the "skip result count" option, if it wasn't already set to
    // FALSE.
    $skip_result_count = $this->query->getOption('skip result count', TRUE);
    if ($skip_result_count) {
      $skip_result_count = !$view->pager->useCountQuery() && empty($view->get_total_rows);
      $this->query->setOption('skip result count', $skip_result_count);
    }

    try {
      // Trigger pager preExecute().
      $view->pager->preExecute($this->query);

      // Views passes sometimes NULL and sometimes the integer 0 for "All" in a
      // pager. If set to 0 items, a string "0" is passed. Therefore, we unset
      // the limit if an empty value OTHER than a string "0" was passed.
      if (!$this->limit && $this->limit !== '0') {
        $this->limit = NULL;
      }
      // Set the range. We always set this, as there might be an offset even if
      // all items are shown.
      $this->query->range($this->offset, $this->limit);

      $start = microtime(TRUE);

      // Execute the search.
      $results = $this->query->execute();
      $this->searchApiResults = $results;

      // Store the results.
      if (!$skip_result_count) {
        $view->pager->total_items = $view->total_rows = $results->getResultCount();
        if (!empty($view->pager->options['offset'])) {
          $view->pager->total_items -= $view->pager->options['offset'];
        }
      }
      $view->result = array();
      if ($results->getResultItems()) {
        $this->addResults($results->getResultItems(), $view);
      }
      $view->execute_time = microtime(TRUE) - $start;

      // Trigger pager postExecute().
      $view->pager->postExecute($view->result);
      $view->pager->updatePageInfo();
    }
    catch (\Exception $e) {
      $this->abort($e->getMessage());
      // Recursion to get the same error behaviour as above.
      $this->execute($view);
    }
  }

  /**
   * Aborts this search query.
   *
   * Used by handlers to flag a fatal error which shouldn't be displayed but
   * still lead to the view returning empty and the search not being executed.
   *
   * @param string|null $msg
   *   Optionally, a translated, unescaped error message to display.
   */
  public function abort($msg = NULL) {
    if ($msg) {
      $this->errors[] = $msg;
    }
    $this->abort = TRUE;
  }

  /**
   * Checks whether this query should be aborted.
   *
   * @return bool
   *   TRUE if the query should/will be aborted, FALSE otherwise.
   *
   * @see SearchApiQuery::abort()
   */
  public function shouldAbort() {
    return $this->abort;
  }

  /**
   * Adds Search API result items to a view's result set.
   *
   * @param \Drupal\search_api\Item\ItemInterface[] $results
   *   The search results.
   * @param \Drupal\views\ViewExecutable $view
   *   The executed view.
   */
  protected function addResults(array $results, ViewExecutable $view) {
    // Views \Drupal\views\Plugin\views\style\StylePluginBase::renderFields()
    // uses a numeric results index to key the rendered results.
    // The ResultRow::index property is the key then used to retrieve these.
    $count = 0;

    // First, unless disabled, check access for all entities in the results.
    if (!$this->options['skip_access'] && $this->getEntityTypes(TRUE)) {
      $account = $this->getAccessAccount();
      foreach ($results as $item_id => $result) {
        $entity_type_id = $result->getDatasource()->getEntityTypeId();
        if (!$entity_type_id) {
          continue;
        }
        $entity = $result->getOriginalObject()->getValue();
        if ($entity instanceof EntityInterface) {
          if (!$entity->access('view', $account)) {
            unset($results[$item_id]);
          }
        }
      }
    }

    foreach ($results as $item_id => $result) {
      $values = array();
      $values['_item'] = $result;
      $object = $result->getOriginalObject(FALSE);
      if ($object) {
        $values['_object'] = $object;
        $values['_relationship_objects'][NULL] = array($object);
      }
      $values['search_api_id'] = $item_id;
      $values['search_api_datasource'] = $result->getDatasourceId();
      $values['search_api_relevance'] = $result->getScore();
      $values['search_api_excerpt'] = $result->getExcerpt() ?: '';

      // Gather any properties from the search results.
      foreach ($result->getFields(FALSE) as $field_id => $field) {
        if ($field->getValues()) {
          $values[$field->getCombinedPropertyPath()] = $field->getValues();
        }
      }

      $values['index'] = $count++;

      $view->result[] = new ResultRow($values);
    }
  }

  /**
   * Retrieves the account object to use for access checks for this query.
   *
   * @return \Drupal\Core\Session\AccountInterface|null
   *   The account for which to check access to returned or displayed entities.
   *   Or NULL to use the currently logged-in user.
   */
  public function getAccessAccount() {
    $account = $this->getOption('search_api_access_account');
    if ($account && is_scalar($account)) {
      $account = User::load($account);
    }
    return $account;
  }

  /**
   * Returns the Search API query object used by this Views query.
   *
   * @return \Drupal\search_api\Query\QueryInterface|null
   *   The search query object used internally by this plugin, if any has been
   *   successfully created. NULL otherwise.
   */
  public function getSearchApiQuery() {
    return $this->query;
  }

  /**
   * Sets the Search API query object.
   *
   * Usually this is done by the query plugin class itself, but in rare cases
   * (such as for caching purposes) it might be necessary to set it from
   * outside.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The new query.
   *
   * @return $this
   */
  public function setSearchApiQuery(QueryInterface $query) {
    $this->query = $query;
    return $this;
  }

  /**
   * Retrieves the Search API result set returned for this query.
   *
   * @return \Drupal\search_api\Query\ResultSetInterface|null
   *   The result set of this query, if it has been retrieved already. NULL
   *   otherwise.
   */
  public function getSearchApiResults() {
    return $this->searchApiResults;
  }

  /**
   * Sets the Search API result set.
   *
   * Usually this is done by the query plugin class itself, but in rare cases
   * (such as for caching purposes) it might be necessary to set it from
   * outside.
   *
   * @param \Drupal\search_api\Query\ResultSetInterface $search_api_results
   *   The result set.
   *
   * @return $this
   */
  public function setSearchApiResults(ResultSetInterface $search_api_results) {
    $this->searchApiResults = $search_api_results;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $dependencies[$this->index->getConfigDependencyKey()][] = $this->index->getConfigDependencyName();
    return $dependencies;
  }

  //
  // Query interface methods (proxy to $this->query)
  //

  /**
   * Creates a new condition group to use with this query object.
   *
   * @param string $conjunction
   *   The conjunction to use for the condition group – either 'AND' or 'OR'.
   * @param string[] $tags
   *   (optional) Tags to set on the condition group.
   *
   * @return \Drupal\search_api\Query\ConditionGroupInterface
   *   A condition group object that is set to use the specified conjunction.
   *
   * @see \Drupal\search_api\Query\QueryInterface::createConditionGroup()
   */
  public function createConditionGroup($conjunction = 'AND', array $tags = array()) {
    if (!$this->shouldAbort()) {
      return $this->query->createConditionGroup($conjunction, $tags);
    }
    return NULL;
  }

  /**
   * Sets the keys to search for.
   *
   * If this method is not called on the query before execution, this will be a
   * filter-only query.
   *
   * @param string|array|null $keys
   *   A string with the search keys, in one of the formats specified by
   *   getKeys(). A passed string will be parsed according to the set parse
   *   mode. Use NULL to not use any search keys.
   *
   * @return $this
   *
   * @see \Drupal\search_api\Query\QueryInterface::keys()
   */
  public function keys($keys = NULL) {
    if (!$this->shouldAbort()) {
      $this->query->keys($keys);
    }
    return $this;
  }

  /**
   * Sets the fields that will be searched for the search keys.
   *
   * If this is not called, all fulltext fields will be searched.
   *
   * @param array $fields
   *   An array containing fulltext fields that should be searched.
   *
   * @return $this
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if one of the fields isn't of type "text".
   *
   * @see \Drupal\search_api\Query\QueryInterface::setFulltextFields()
   */
  public function setFulltextFields($fields = NULL) {
    if (!$this->shouldAbort()) {
      $this->query->setFulltextFields($fields);
    }
    return $this;
  }

  /**
   * Adds a nested condition group.
   *
   * If $group is given, the filter is added to the relevant filter group
   * instead.
   *
   * @param \Drupal\search_api\Query\ConditionGroupInterface $condition_group
   *   A condition group that should be added.
   * @param string|null $group
   *   (optional) The Views query filter group to add this filter to.
   *
   * @return $this
   *
   * @see \Drupal\search_api\Query\QueryInterface::addConditionGroup()
   */
  public function addConditionGroup(ConditionGroupInterface $condition_group, $group = NULL) {
    if (!$this->shouldAbort()) {
      // Ensure all variants of 0 are actually 0. Thus '', 0 and NULL are all
      // the default group.
      if (empty($group)) {
        $group = 0;
      }
      $this->conditions[$group]['condition_groups'][] = $condition_group;
    }
    return $this;
  }

  /**
   * Adds a new ($field $operator $value) condition filter.
   *
   * @param string $field
   *   The field to filter on, e.g. 'title'. The special field
   *   "search_api_datasource" can be used to filter by datasource ID.
   * @param mixed $value
   *   The value the field should have (or be related to by the operator).
   * @param string $operator
   *   The operator to use for checking the constraint. The following operators
   *   are supported for primitive types: "=", "<>", "<", "<=", ">=", ">". They
   *   have the same semantics as the corresponding SQL operators.
   *   If $field is a fulltext field, $operator can only be "=" or "<>", which
   *   are in this case interpreted as "contains" or "doesn't contain",
   *   respectively.
   *   If $value is NULL, $operator also can only be "=" or "<>", meaning the
   *   field must have no or some value, respectively.
   * @param string|null $group
   *   (optional) The Views query filter group to add this filter to.
   *
   * @return $this
   *
   * @see \Drupal\search_api\Query\QueryInterface::addCondition()
   */
  public function addCondition($field, $value, $operator = '=', $group = NULL) {
    if (!$this->shouldAbort()) {
      // Ensure all variants of 0 are actually 0. Thus '', 0 and NULL are all
      // the default group.
      if (empty($group)) {
        $group = 0;
      }
      $condition = array($field, $value, $operator);
      $this->conditions[$group]['conditions'][] = $condition;
    }
    return $this;
  }

  /**
   * Adds a simple condition to the query.
   *
   * This replicates the interface of Views' default SQL backend to simplify
   * the Views integration of the Search API. If you are writing Search
   * API-specific Views code, you should better use the filter() or condition()
   * methods.
   *
   * @param int $group
   *   The condition group to add these to; groups are used to create AND/OR
   *   sections. Groups cannot be nested. Use 0 as the default group.
   *   If the group does not yet exist it will be created as an AND group.
   * @param string|\Drupal\Core\Database\Query\ConditionInterface|\Drupal\search_api\Query\ConditionGroupInterface $field
   *   The ID of the field to check; or a filter object to add to the query; or,
   *   for compatibility purposes, a database condition object to transform into
   *   a search filter object and add to the query. If a field ID is passed and
   *   starts with a period (.), it will be stripped.
   * @param mixed $value
   *   (optional) The value the field should have (or be related to by the
   *   operator). Or NULL if an object is passed as $field.
   * @param string|null $operator
   *   (optional) The operator to use for checking the constraint. The following
   *   operators are supported for primitive types: "=", "<>", "<", "<=", ">=",
   *   ">". They have the same semantics as the corresponding SQL operators.
   *   If $field is a fulltext field, $operator can only be "=" or "<>", which
   *   are in this case interpreted as "contains" or "doesn't contain",
   *   respectively.
   *   If $value is NULL, $operator also can only be "=" or "<>", meaning the
   *   field must have no or some value, respectively.
   *   To stay compatible with Views, "!=" is supported as an alias for "<>".
   *   If an object is passed as $field, $operator should be NULL.
   *
   * @return $this
   *
   * @see \Drupal\views\Plugin\views\query\Sql::addWhere()
   * @see \Drupal\search_api\Plugin\views\query\SearchApiQuery::filter()
   * @see \Drupal\search_api\Plugin\views\query\SearchApiQuery::condition()
   */
  public function addWhere($group, $field, $value = NULL, $operator = NULL) {
    if ($this->shouldAbort()) {
      return $this;
    }

    // Ensure all variants of 0 are actually 0. Thus '', 0 and NULL are all the
    // default group.
    if (empty($group)) {
      $group = 0;
    }

    if (is_object($field)) {
      if ($field instanceof ConditionInterface) {
        $field = $this->transformDbCondition($field);
      }
      if ($field instanceof ConditionGroupInterface) {
        $this->conditions[$group]['condition_groups'][] = $field;
      }
      elseif (!$this->shouldAbort()) {
        // We only need to abort  if that wasn't done by transformDbCondition()
        // already.
        $this->abort('Unexpected condition passed to addWhere().');
      }
    }
    else {
      $condition = array(
        $this->sanitizeFieldId($field),
        $value,
        $this->sanitizeOperator($operator),
      );
      $this->conditions[$group]['conditions'][] = $condition;
    }

    return $this;
  }

  /**
   * Transforms a database condition to an equivalent search filter.
   *
   * @param \Drupal\Core\Database\Query\ConditionInterface $db_condition
   *   The condition to transform.
   *
   * @return \Drupal\search_api\Query\ConditionGroupInterface|null
   *   A search filter equivalent to $condition, or NULL if the transformation
   *   failed.
   */
  protected function transformDbCondition(ConditionInterface $db_condition) {
    $conditions = $db_condition->conditions();
    $filter = $this->query->createConditionGroup($conditions['#conjunction']);
    unset($conditions['#conjunction']);
    foreach ($conditions as $condition) {
      if ($condition['operator'] === NULL) {
        $this->abort('Trying to include a raw SQL condition in a Search API query.');
        return NULL;
      }
      if ($condition['field'] instanceof ConditionInterface) {
        $nested_filter = $this->transformDbCondition($condition['field']);
        if ($nested_filter) {
          $filter->addConditionGroup($nested_filter);
        }
        else {
          return NULL;
        }
      }
      else {
        $filter->addCondition($this->sanitizeFieldId($condition['field']), $condition['value'], $this->sanitizeOperator($condition['operator']));
      }
    }
    return $filter;
  }

  /**
   * Adapts a field ID for use in a Search API query.
   *
   * This method will remove a leading period (.), if present. This is done
   * because in the SQL Views query plugin field IDs are always prefixed with a
   * table alias (in our case always empty) followed by a period.
   *
   * @param string $field_id
   *   The field ID to adapt for use in the Search API.
   *
   * @return string
   *   The sanitized field ID.
   */
  protected function sanitizeFieldId($field_id) {
    if ($field_id && $field_id[0] === '.') {
      $field_id = substr($field_id, 1);
    }
    return $field_id;
  }

  /**
   * Adapts an operator for use in a Search API query.
   *
   * This method maps Views' "!=" to the "<>" Search API uses.
   *
   * @param string $operator
   *   The operator to adapt for use in the Search API.
   *
   * @return string
   *   The sanitized operator.
   */
  protected function sanitizeOperator($operator) {
    if ($operator === '!=') {
      $operator = '<>';
    }
    return $operator;
  }

  /**
   * Adds a sort directive to this search query.
   *
   * If no sort is manually set, the results will be sorted descending by
   * relevance.
   *
   * @param string $field
   *   The field to sort by. The special fields 'search_api_relevance' (sort by
   *   relevance) and 'search_api_id' (sort by item id) may be used.
   * @param string $order
   *   The order to sort items in - either 'ASC' or 'DESC'.
   *
   * @return $this
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if the field is multi-valued or of a fulltext type.
   *
   * @see \Drupal\search_api\Query\QueryInterface::sort()
   */
  public function sort($field, $order = 'ASC') {
    if (!$this->shouldAbort()) {
      $this->query->sort($field, $order);
    }
    return $this;
  }

  /**
   * Adds an ORDER BY clause to the query.
   *
   * This replicates the interface of Views' default SQL backend to simplify
   * the Views integration of the Search API. If you are writing Search
   * API-specific Views code, you should better use the sort() method directly.
   *
   * Currently, only random sorting (by passing "rand" as the table) is
   * supported (for backends that support it), all other calls are silently
   * ignored.
   *
   * @param string|null $table
   *   The table this field is part of. If a formula, enter NULL. If you want to
   *   order the results randomly, use "rand" as table and nothing else.
   * @param string|null $field
   *   (optional) The field or formula to sort on. If already a field, enter
   *   NULL and put in the alias.
   * @param string $order
   *   (optional) Either ASC or DESC.
   * @param string $alias
   *   (optional) The alias to add the field as. In SQL, all fields in the order
   *   by must also be in the SELECT portion. If an $alias isn't specified one
   *   will be generated for from the $field; however, if the $field is a
   *   formula, this alias will likely fail.
   * @param array $params
   *   (optional) Any parameters that should be passed through to the addField()
   *   call.
   *
   * @see \Drupal\views\Plugin\views\query\Sql::addOrderBy()
   */
  public function addOrderBy($table, $field = NULL, $order = 'ASC', $alias = '', $params = array()) {
    $server = $this->getIndex()->getServerInstance();
    if ($table == 'rand') {
      if ($server->supportsFeature('search_api_random_sort')) {
        $this->sort('search_api_random', $order);
        if ($params) {
          $this->setOption('search_api_random_sort', $params);
        }
      }
      else {
        $variables['%server'] = $server->label();
        $this->getLogger()->warning('Tried to sort results randomly on server %server which does not support random sorting.', $variables);
      }
    }
  }

  /**
   * Adds a range of results to return.
   *
   * This will be saved in the query's options. If called without parameters,
   * this will remove all range restrictions previously set.
   *
   * @param int|null $offset
   *   The zero-based offset of the first result returned.
   * @param int|null $limit
   *   The number of results to return.
   *
   * @return $this
   *
   * @see \Drupal\search_api\Query\QueryInterface::range()
   */
  public function range($offset = NULL, $limit = NULL) {
    if (!$this->shouldAbort()) {
      $this->query->range($offset, $limit);
    }
    return $this;
  }

  /**
   * Retrieves the index associated with this search.
   *
   * @return \Drupal\search_api\IndexInterface
   *   The search index this query should be executed on.
   *
   * @see \Drupal\search_api\Query\QueryInterface::getIndex()
   */
  public function getIndex() {
    return $this->index;
  }

  /**
   * Retrieves the search keys for this query.
   *
   * @return array|string|null
   *   This object's search keys - either a string or an array specifying a
   *   complex search expression.
   *   An array will contain a '#conjunction' key specifying the conjunction
   *   type, and search strings or nested expression arrays at numeric keys.
   *   Additionally, a '#negation' key might be present, which means – unless it
   *   maps to a FALSE value – that the search keys contained in that array
   *   should be negated, i.e. not be present in returned results. The negation
   *   works on the whole array, not on each contained term individually – i.e.,
   *   with the "AND" conjunction and negation, only results that contain all
   *   the terms in the array should be excluded; with the "OR" conjunction and
   *   negation, all results containing one or more of the terms in the array
   *   should be excluded.
   *
   * @see keys()
   *
   * @see \Drupal\search_api\Query\QueryInterface::getKeys()
   */
  public function &getKeys() {
    if (!$this->shouldAbort()) {
      return $this->query->getKeys();
    }
    $ret = NULL;
    return $ret;
  }

  /**
   * Retrieves the unparsed search keys for this query as originally entered.
   *
   * @return array|string|null
   *   The unprocessed search keys, exactly as passed to this object. Has the
   *   same format as the return value of getKeys().
   *
   * @see keys()
   *
   * @see \Drupal\search_api\Query\QueryInterface::getOriginalKeys()
   */
  public function getOriginalKeys() {
    if (!$this->shouldAbort()) {
      return $this->query->getOriginalKeys();
    }
    return NULL;
  }

  /**
   * Retrieves the fulltext fields that will be searched for the search keys.
   *
   * @return string[]|null
   *   An array containing the fields that should be searched for the search
   *   keys.
   *
   * @see setFulltextFields()
   * @see \Drupal\search_api\Query\QueryInterface::getFulltextFields()
   */
  public function &getFulltextFields() {
    if (!$this->shouldAbort()) {
      return $this->query->getFulltextFields();
    }
    $ret = NULL;
    return $ret;
  }

  /**
   * Retrieves the filter object associated with this search query.
   *
   * @return \Drupal\search_api\Query\ConditionGroupInterface
   *   This object's associated filter object.
   *
   * @see \Drupal\search_api\Query\QueryInterface::getConditionGroup()
   */
  public function getFilter() {
    if (!$this->shouldAbort()) {
      return $this->query->getConditionGroup();
    }
    return NULL;
  }

  /**
   * Retrieves the sorts set for this query.
   *
   * @return array
   *   An array specifying the sort order for this query. Array keys are the
   *   field names in order of importance, the values are the respective order
   *   in which to sort the results according to the field.
   *
   * @see sort()
   *
   * @see \Drupal\search_api\Query\QueryInterface::getSorts()
   */
  public function &getSort() {
    if (!$this->shouldAbort()) {
      return $this->query->getSorts();
    }
    $ret = NULL;
    return $ret;
  }

  /**
   * Retrieves an option set on this search query.
   *
   * @param string $name
   *   The name of an option.
   * @param mixed $default
   *   The value to return if the specified option is not set.
   *
   * @return mixed
   *   The value of the option with the specified name, if set. NULL otherwise.
   *
   * @see \Drupal\search_api\Query\QueryInterface::getOption()
   */
  public function getOption($name, $default = NULL) {
    if (!$this->shouldAbort()) {
      return $this->query->getOption($name, $default);
    }
    return $default;
  }

  /**
   * Sets an option for this search query.
   *
   * @param string $name
   *   The name of an option. The following options are recognized by default:
   *   - conjunction: The type of conjunction to use for this query – either
   *     'AND' or 'OR'. 'AND' by default. This only influences the search keys,
   *     filters will always use AND by default.
   *   - 'parse mode': The mode with which to parse the $keys variable, if it
   *     is set and not already an array. See
   *     \Drupal\search_api\Query\Query::parseModes() for recognized parse
   *     modes.
   *   - offset: The position of the first returned search results relative to
   *     the whole result in the index.
   *   - limit: The maximum number of search results to return. -1 means no
   *     limit.
   *   - 'search id': A string that will be used as the identifier when storing
   *     this search in the Search API's static cache.
   *   - 'skip result count': If present and set to TRUE, the search's result
   *     count will not be needed. Service classes can check for this option to
   *     possibly avoid executing expensive operations to compute the result
   *     count in cases where it is not needed.
   *   - search_api_access_account: The account which will be used for entity
   *     access checks, if available and enabled for the index.
   *   - search_api_bypass_access: If set to TRUE, entity access checks will be
   *     skipped, even if enabled for the index.
   *   However, contrib modules might introduce arbitrary other keys with their
   *   own, special meaning. (Usually they should be prefixed with the module
   *   name, though, to avoid conflicts.)
   * @param mixed $value
   *   The new value of the option.
   *
   * @return mixed
   *   The option's previous value, or NULL if none was set.
   *
   * @see \Drupal\search_api\Query\QueryInterface::setOption()
   */
  public function setOption($name, $value) {
    if (!$this->shouldAbort()) {
      return $this->query->setOption($name, $value);
    }
    return NULL;
  }

  /**
   * Retrieves all options set for this search query.
   *
   * The return value is a reference to the options so they can also be altered
   * this way.
   *
   * @return array
   *   An associative array of query options.
   *
   * @see \Drupal\search_api\Query\QueryInterface::getOptions()
   */
  public function &getOptions() {
    if (!$this->shouldAbort()) {
      return $this->query->getOptions();
    }
    $ret = NULL;
    return $ret;
  }

  //
  // Methods from Views' SQL query plugin, to simplify integration.
  //

  /**
   * Ensures a table exists in the query.
   *
   * This replicates the interface of Views' default SQL backend to simplify
   * the Views integration of the Search API. Since the Search API has no
   * concept of "tables", this method implementation does nothing. If you are
   * writing Search API-specific Views code, there is therefore no reason at all
   * to call this method.
   * See https://www.drupal.org/node/2484565 for more information.
   *
   * @return string
   *   An empty string.
   */
  public function ensureTable() {
    return '';
  }

}

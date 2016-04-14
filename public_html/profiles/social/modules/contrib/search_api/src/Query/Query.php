<?php

namespace Drupal\search_api\Query;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\SearchApiException;

/**
 * Provides a standard implementation for a Search API query.
 */
class Query implements QueryInterface {

  use StringTranslationTrait;

  /**
   * The index on which the query will be executed.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * The index's ID.
   *
   * Used when serializing, to avoid serializing the index, too.
   *
   * @var string|null
   */
  protected $indexId;

  /**
   * The result cache service.
   *
   * @var \Drupal\search_api\Query\ResultsCacheInterface
   */
  protected $resultsCache;

  /**
   * The parse mode to use for fulltext search keys.
   *
   * @var string
   *
   * @see \Drupal\search_api\Query\QueryInterface::parseModes()
   */
  protected $parseMode = 'terms';

  /**
   * The search keys.
   *
   * If NULL, this will be a filter-only search.
   *
   * @var mixed
   */
  protected $keys;

  /**
   * The unprocessed search keys, as passed to the keys() method.
   *
   * @var mixed
   */
  protected $origKeys;

  /**
   * The fulltext fields that will be searched for the keys.
   *
   * @var array
   */
  protected $fields;

  /**
   * The root condition group associated with this query.
   *
   * @var \Drupal\search_api\Query\ConditionGroupInterface
   */
  protected $conditionGroup;

  /**
   * The sorts associated with this query.
   *
   * @var array
   */
  protected $sorts = array();

  /**
   * Options configuring this query.
   *
   * @var array
   */
  protected $options;

  /**
   * The tags set on this query.
   *
   * @var string[]
   */
  protected $tags = array();

  /**
   * Flag for whether preExecute() was already called for this query.
   *
   * @var bool
   */
  protected $preExecuteRan = FALSE;

  /**
   * Constructs a Query object.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index the query should be executed on.
   * @param \Drupal\search_api\Query\ResultsCacheInterface $results_cache
   *   The results cache that should be used for this query.
   * @param array $options
   *   (optional) Associative array of options configuring this query. See
   *   \Drupal\search_api\Query\QueryInterface::setOption() for a list of
   *   options that are recognized by default.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if a search on that index (or with those options) won't be
   *   possible.
   */
  public function __construct(IndexInterface $index, ResultsCacheInterface $results_cache, array $options = array()) {
    if (!$index->status()) {
      throw new SearchApiException(new FormattableMarkup("Can't search on index %index which is disabled.", array('%index' => $index->label())));
    }
    $this->index = $index;
    $this->resultsCache = $results_cache;
    $this->options = $options + array(
      'conjunction' => 'AND',
      'search id' => __CLASS__,
    );
    $this->conditionGroup = $this->createConditionGroup('AND');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(IndexInterface $index, ResultsCacheInterface $results_cache, array $options = array()) {
    return new static($index, $results_cache, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function parseModes() {
    $modes['direct'] = array(
      'name' => $this->t('Direct query'),
      'description' => $this->t("Don't parse the query, just hand it to the search server unaltered. Might fail if the query contains syntax errors in regard to the specific server's query syntax."),
    );
    $modes['single'] = array(
      'name' => $this->t('Single term'),
      'description' => $this->t('The query is interpreted as a single keyword, maybe containing spaces or special characters.'),
    );
    $modes['terms'] = array(
      'name' => $this->t('Multiple terms'),
      'description' => $this->t('The query is interpreted as multiple keywords separated by spaces. Keywords containing spaces may be "quoted". Quoted keywords must still be separated by spaces.'),
    );
    // @todo Add fourth mode for complicated expressions, e.g.: »"vanilla ice" OR (love NOT hate)«
    return $modes;
  }

  /**
   * {@inheritdoc}
   */
  public function getParseMode() {
    return $this->parseMode;
  }

  /**
   * {@inheritdoc}
   */
  public function setParseMode($parse_mode) {
    $this->parseMode = $parse_mode;
    return $this;
  }

  /**
   * Parses search keys input by the user according to the given parse mode.
   *
   * @param string|array|null $keys
   *   The keywords to parse.
   *
   * @return array|null|string
   *   The parsed keywords, in the format defined by
   *   \Drupal\search_api\Query\QueryInterface::getKeys().
   *
   * @see \Drupal\search_api\Query\QueryInterface::parseModes()
   */
  protected function parseKeys($keys) {
    if ($keys === NULL || is_array($keys)) {
      return $keys;
    }
    $keys = '' . $keys;
    switch ($this->parseMode) {
      case 'direct':
        return $keys;

      case 'single':
        return array('#conjunction' => $this->options['conjunction'], $keys);

      case 'terms':
        $ret = explode(' ', $keys);
        $quoted = FALSE;
        $str = '';
        foreach ($ret as $k => $v) {
          if (!$v) {
            continue;
          }
          if ($quoted) {
            if (substr($v, -1) == '"') {
              $v = substr($v, 0, -1);
              $str .= ' ' . $v;
              $ret[$k] = $str;
              $quoted = FALSE;
            }
            else {
              $str .= ' ' . $v;
              unset($ret[$k]);
            }
          }
          elseif ($v[0] == '"') {
            $len = strlen($v);
            if ($len > 1 && $v[$len - 1] == '"') {
              $ret[$k] = substr($v, 1, -1);
            }
            else {
              $str = substr($v, 1);
              $quoted = TRUE;
              unset($ret[$k]);
            }
          }
        }
        if ($quoted) {
          $ret[] = $str;
        }
        $ret['#conjunction'] = $this->options['conjunction'];
        return array_filter($ret);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function createConditionGroup($conjunction = 'AND', array $tags = array()) {
    return new ConditionGroup($conjunction, $tags);
  }

  /**
   * {@inheritdoc}
   */
  public function keys($keys = NULL) {
    $this->origKeys = $keys;
    if (isset($keys)) {
      $this->keys = $this->parseKeys($keys);
    }
    else {
      $this->keys = NULL;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setFulltextFields(array $fields = NULL) {
    $this->fields = $fields;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addConditionGroup(ConditionGroupInterface $condition_group) {
    $this->conditionGroup->addConditionGroup($condition_group);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addCondition($field, $value, $operator = '=') {
    $this->conditionGroup->addCondition($field, $value, $operator);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function sort($field, $order = self::SORT_ASC) {
    $order = strtoupper(trim($order)) == self::SORT_DESC ? self::SORT_DESC : self::SORT_ASC;
    $this->sorts[$field] = $order;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function range($offset = NULL, $limit = NULL) {
    $this->options['offset'] = $offset;
    $this->options['limit'] = $limit;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    // Prepare the query for execution by the server.
    $this->preExecute();

    // Execute query.
    $response = $this->index->getServerInstance()->search($this);

    // Postprocess the search results.
    $this->postExecute($response);

    // Store search for later retrieval for facets, etc.
    // @todo Figure out how to store the executed searches for the request.
    // search_api_current_search(NULL, $this, $response);
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function preExecute() {
    // Make sure to only execute this once per query.
    if (!$this->preExecuteRan) {
      $this->preExecuteRan = TRUE;

      // Preprocess query.
      $this->index->preprocessSearchQuery($this);

      // Let modules alter the query.
      $hooks = array('search_api_query');
      foreach ($this->tags as $tag) {
        $hooks[] = "search_api_query_$tag";
      }
      \Drupal::moduleHandler()->alter($hooks, $this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postExecute(ResultSetInterface $results) {
    // Postprocess results.
    $this->index->postprocessSearchResults($results);

    // Let modules alter the results.
    $hooks = array('search_api_results');
    foreach ($this->tags as $tag) {
      $hooks[] = "search_api_results_$tag";
    }
    \Drupal::moduleHandler()->alter($hooks, $results);

    // Store the results in the static cache.
    $this->resultsCache->addResults($results);
  }

  /**
   * {@inheritdoc}
   */
  public function getIndex() {
    return $this->index;
  }

  /**
   * {@inheritdoc}
   */
  public function &getKeys() {
    return $this->keys;
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalKeys() {
    return $this->origKeys;
  }

  /**
   * {@inheritdoc}
   */
  public function &getFulltextFields() {
    return $this->fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getConditionGroup() {
    return $this->conditionGroup;
  }

  /**
   * {@inheritdoc}
   */
  public function &getSorts() {
    return $this->sorts;
  }

  /**
   * {@inheritdoc}
   */
  public function getOption($name, $default = NULL) {
    return array_key_exists($name, $this->options) ? $this->options[$name] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function setOption($name, $value) {
    $old = $this->getOption($name);
    $this->options[$name] = $value;
    return $old;
  }

  /**
   * {@inheritdoc}
   */
  public function &getOptions() {
    return $this->options;
  }

  /**
   * Implements the magic __sleep() method to avoid serializing the index.
   */
  public function __sleep() {
    $this->indexId = $this->index->id();
    $keys = get_object_vars($this);
    unset($keys['index'], $keys['resultsCache'], $keys['stringTranslation']);
    return array_keys($keys);
  }

  /**
   * Implements the magic __wakeup() method to reload the query's index.
   */
  public function __wakeup() {
    if (!isset($this->index) && !empty($this->indexId)) {
      $this->index = \Drupal::entityTypeManager()->getStorage('search_api_index')->load($this->indexId);
      unset($this->indexId);
    }
  }

  /**
   * Implements the magic __toString() method to simplify debugging.
   */
  public function __toString() {
    $ret = 'Index: ' . $this->index->id() . "\n";
    $ret .= 'Keys: ' . str_replace("\n", "\n  ", var_export($this->origKeys, TRUE)) . "\n";
    if (isset($this->keys)) {
      $ret .= 'Parsed keys: ' . str_replace("\n", "\n  ", var_export($this->keys, TRUE)) . "\n";
      $ret .= 'Searched fields: ' . (isset($this->fields) ? implode(', ', $this->fields) : '[ALL]') . "\n";
    }
    if ($conditions = (string) $this->conditionGroup) {
      $conditions = str_replace("\n", "\n  ", $conditions);
      $ret .= "Conditions:\n  $conditions\n";
    }
    if ($this->sorts) {
      $sorts = array();
      foreach ($this->sorts as $field => $order) {
        $sorts[] = "$field $order";
      }
      $ret .= 'Sorting: ' . implode(', ', $sorts) . "\n";
    }
    // @todo Fix for entities contained in options (which might kill
    //   var_export() due to circular references).
    $ret .= 'Options: ' . str_replace("\n", "\n  ", var_export($this->options, TRUE)) . "\n";
    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function addTag($tag) {
    $this->tags[$tag] = $tag;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTag($tag) {
    return isset($this->tags[$tag]);
  }

  /**
   * {@inheritdoc}
   */
  public function hasAllTags() {
    return !array_diff_key(array_flip(func_get_args()), $this->tags);
  }

  /**
   * {@inheritdoc}
   */
  public function hasAnyTag() {
    return (bool) array_intersect_key(array_flip(func_get_args()), $this->tags);
  }

  /**
   * {@inheritdoc}
   */
  public function &getTags() {
    return $this->tags;
  }

}

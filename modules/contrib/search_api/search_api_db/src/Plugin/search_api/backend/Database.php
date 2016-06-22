<?php

namespace Drupal\search_api_db\Plugin\search_api\backend;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Database as CoreDatabase;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Render\Element;
use Drupal\search_api\Backend\BackendPluginBase;
use Drupal\search_api\DataType\DataTypePluginManager;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Query\ConditionGroupInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Utility;
use Drupal\search_api_db\DatabaseCompatibility\DatabaseCompatibilityHandlerInterface;
use Drupal\search_api_db\DatabaseCompatibility\GenericDatabase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Indexes items in a database.
 *
 * @SearchApiBackend(
 *   id = "search_api_db",
 *   label = @Translation("Database"),
 *   description = @Translation("Indexes items in the database. Supports several advanced features, but should not be used for large sites.")
 * )
 */
class Database extends BackendPluginBase {

  /**
   * Multiplier for scores to have precision when converted from float to int.
   */
  const SCORE_MULTIPLIER = 1000;

  /**
   * The ID of the key-value store in which the indexes' DB infos are stored.
   */
  const INDEXES_KEY_VALUE_STORE_ID = 'search_api_db.indexes';

  /**
   * The database connection to use for this server.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * DBMS compatibility handler for this type of database.
   *
   * @var \Drupal\search_api_db\DatabaseCompatibility\DatabaseCompatibilityHandlerInterface
   */
  protected $dbmsCompatibility;

  /**
   * The module handler to use.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|null
   */
  protected $moduleHandler;

  /**
   * The config factory to use.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|null
   */
  protected $configFactory;

  /**
   * The data type plugin manager to use.
   *
   * @var \Drupal\search_api\DataType\DataTypePluginManager
   */
  protected $dataTypePluginManager;

  /**
   * The logger to use for logging messages.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|null
   */
  protected $logger;

  /**
   * The key-value store to use.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValueStore;

  /**
   * The transliteration service to use.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliterator;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface|null
   */
  protected $dateFormatter;

  /**
   * The keywords ignored during the current search query.
   *
   * @var array
   */
  protected $ignored = array();

  /**
   * All warnings for the current search query.
   *
   * @var array
   */
  protected $warnings = array();

  /**
   * Constructs a Database object.
   *
   * @param array $configuration
   *   A configuration array containing settings for this backend.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    if (isset($configuration['database'])) {
      list($key, $target) = explode(':', $configuration['database'], 2);
      // @todo Can we somehow get the connection in a dependency-injected way?
      $this->database = CoreDatabase::getConnection($target, $key);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $backend */
    $backend = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
    $module_handler = $container->get('module_handler');
    $backend->setModuleHandler($module_handler);

    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $container->get('config.factory');
    $backend->setConfigFactory($config_factory);

    /** @var \Drupal\search_api\DataType\DataTypePluginManager $data_type_plugin_manager */
    $data_type_plugin_manager = $container->get('plugin.manager.search_api.data_type');
    $backend->setDataTypePluginManager($data_type_plugin_manager);

    /** @var \Drupal\Core\Logger\LoggerChannelInterface $logger */
    $logger = $container->get('logger.factory')->get('search_api_db');
    $backend->setLogger($logger);

    /** @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $keyvalue_factory */
    $keyvalue_factory = $container->get('keyvalue');
    $backend->setKeyValueStore($keyvalue_factory->get(self::INDEXES_KEY_VALUE_STORE_ID));

    // For a new backend plugin, the database might not be set yet. In that case
    // we of course also don't need a DBMS compatibility handler.
    if ($backend->getDatabase()) {
      /** @var \Drupal\search_api_db\DatabaseCompatibility\DatabaseCompatibilityHandlerInterface $dbms_compatibility_handler */
      $dbms_compatibility_handler = $container->get('search_api_db.database_compatibility');
      // Make sure that we actually provide a handler for the right database,
      // otherwise fall back to the generic handler.
      if ($dbms_compatibility_handler->getDatabase() != $backend->getDatabase()) {
        /** @var \Drupal\Component\Transliteration\TransliterationInterface $transliterator */
        $transliterator = $container->get('transliteration');
        $dbms_compatibility_handler = new GenericDatabase($backend->getDatabase(), $transliterator);
      }
      $backend->setDbmsCompatibilityHandler($dbms_compatibility_handler);
    }

    return $backend;
  }

  /**
   * Retrieves the database connection used by this backend.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection.
   */
  public function getDatabase() {
    return $this->database;
  }

  /**
   * Returns the module handler to use for this plugin.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   */
  public function getModuleHandler() {
    return $this->moduleHandler ?: \Drupal::moduleHandler();
  }

  /**
   * Sets the module handler to use for this plugin.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to use for this plugin.
   *
   * @return $this
   */
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
    return $this;
  }

  /**
   * Returns the config factory to use for this plugin.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   The config factory.
   */
  public function getConfigFactory() {
    return $this->configFactory ?: \Drupal::configFactory();
  }

  /**
   * Sets the config factory to use for this plugin.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory to use for this plugin.
   *
   * @return $this
   */
  public function setConfigFactory(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    return $this;
  }

  /**
   * Retrieves the data type plugin manager.
   *
   * @return \Drupal\search_api\DataType\DataTypePluginManager
   *   The data type plugin manager.
   */
  public function getDataTypePluginManager() {
    return $this->dataTypePluginManager ?: \Drupal::service('plugin.manager.search_api.data_type');
  }

  /**
   * Sets the data type plugin manager.
   *
   * @param \Drupal\search_api\DataType\DataTypePluginManager $data_type_plugin_manager
   *   The new data type plugin manager.
   *
   * @return $this
   */
  public function setDataTypePluginManager(DataTypePluginManager $data_type_plugin_manager) {
    $this->dataTypePluginManager = $data_type_plugin_manager;
    return $this;
  }

  /**
   * Retrieves the logger to use.
   *
   * @return \Drupal\Core\Logger\LoggerChannelInterface
   *   The logger to use.
   */
  public function getLogger() {
    return $this->logger ?: \Drupal::service('logger.factory')->get('search_api_db');
  }

  /**
   * Sets the logger to use.
   *
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger to use.
   *
   * @return $this
   */
  public function setLogger(LoggerChannelInterface $logger) {
    $this->logger = $logger;
    return $this;
  }

  /**
   * Retrieves the key-value store to use.
   *
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   *   The key-value store.
   */
  public function getKeyValueStore() {
    return $this->keyValueStore ?: \Drupal::keyValue(self::INDEXES_KEY_VALUE_STORE_ID);
  }

  /**
   * Sets the key-value store to use.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreInterface $key_value_store
   *   The key-value store.
   *
   * @return $this
   */
  public function setKeyValueStore(KeyValueStoreInterface $key_value_store) {
    $this->keyValueStore = $key_value_store;
    return $this;
  }

  /**
   * Retrieves the date formatter.
   *
   * @return \Drupal\Core\Datetime\DateFormatterInterface
   *   The date formatter.
   */
  public function getDateFormatter() {
    return $this->dateFormatter ?: \Drupal::service('date.formatter');
  }

  /**
   * Sets the date formatter.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The new date formatter.
   *
   * @return $this
   */
  public function setDateFormatter(DateFormatterInterface $date_formatter) {
    $this->dateFormatter = $date_formatter;
    return $this;
  }

  /**
   * Sets the DBMS compatibility handler.
   *
   * @param \Drupal\search_api_db\DatabaseCompatibility\DatabaseCompatibilityHandlerInterface $handler
   *   The DBMS compatibility handler.
   *
   * @return $this
   */
  protected function setDbmsCompatibilityHandler(DatabaseCompatibilityHandlerInterface $handler) {
    $this->dbmsCompatibility = $handler;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'database' => NULL,
      'min_chars' => 1,
      'partial_matches' => FALSE,
      'autocomplete' => array(
        'suggest_suffix' => TRUE,
        'suggest_words' => TRUE,
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Discern between creation and editing of a server, since we don't allow
    // the database to be changed later on.
    if (!$this->configuration['database']) {
      $options = array();
      $key = $target = '';
      foreach (CoreDatabase::getAllConnectionInfo() as $key => $targets) {
        foreach ($targets as $target => $info) {
          $options[$key]["$key:$target"] = "$key » $target";
        }
      }
      if (count($options) > 1 || count(reset($options)) > 1) {
        $form['database'] = array(
          '#type' => 'select',
          '#title' => $this->t('Database'),
          '#description' => $this->t('Select the database key and target to use for storing indexing information in. Cannot be changed after creation.'),
          '#options' => $options,
          '#default_value' => 'default:default',
          '#required' => TRUE,
        );
      }
      else {
        $form['database'] = array(
          '#type' => 'value',
          '#value' => "$key:$target",
        );
      }
    }
    else {
      $form = array(
        'database' => array(
          '#type' => 'value',
          '#title' => $this->t('Database'),
          '#value' => $this->configuration['database'],
        ),
        'database_text' => array(
          '#type' => 'item',
          '#title' => $this->t('Database'),
          '#plain_text' => str_replace(':', ' > ', $this->configuration['database']),
        ),
      );
    }

    $form['min_chars'] = array(
      '#type' => 'select',
      '#title' => $this->t('Minimum word length'),
      '#description' => $this->t('The minimum number of characters a word must consist of to be indexed'),
      '#options' => array_combine(
        array(1, 2, 3, 4, 5, 6),
        array(1, 2, 3, 4, 5, 6)
      ),
      '#default_value' => $this->configuration['min_chars'],
    );

    $form['partial_matches'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Search on parts of a word'),
      '#description' => $this->t('Find keywords in parts of a word, too. (E.g., find results with "database" when searching for "base"). <strong>Caution:</strong> This can make searches much slower on large sites!'),
      '#default_value' => $this->configuration['partial_matches'],
    );

    if ($this->getModuleHandler()->moduleExists('search_api_autocomplete')) {
      $form['autocomplete'] = array(
        '#type' => 'details',
        '#title' => $this->t('Autocomplete settings'),
        '#description' => $this->t('These settings allow you to configure how suggestions are computed when autocompletion is used. If you are seeing many inappropriate suggestions you might want to deactivate the corresponding suggestion type. You can also deactivate one method to speed up the generation of suggestions.'),
      );
      $form['autocomplete']['suggest_suffix'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Suggest word endings'),
        '#description' => $this->t('Suggest endings for the currently entered word.'),
        '#default_value' => $this->configuration['autocomplete']['suggest_suffix'],
      );
      $form['autocomplete']['suggest_words'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Suggest additional words'),
        '#description' => $this->t('Suggest additional words the user might want to search for.'),
        '#default_value' => $this->configuration['autocomplete']['suggest_words'],
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewSettings() {
    $info = array();

    $info[] = array(
      'label' => $this->t('Database'),
      'info' => str_replace(':', ' > ', $this->configuration['database']),
    );
    if ($this->configuration['min_chars'] > 1) {
      $info[] = array(
        'label' => $this->t('Minimum word length'),
        'info' => $this->configuration['min_chars'],
      );
    }
    $info[] = array(
      'label' => $this->t('Search on parts of a word'),
      'info' => !empty($this->configuration['partial_matches']) ? $this->t('enabled') : $this->t('disabled'),
    );
    if (!empty($this->configuration['autocomplete'])) {
      $this->configuration['autocomplete'] += array(
        'suggest_suffix' => TRUE,
        'suggest_words' => TRUE,
      );
      $autocomplete_modes = array();
      if ($this->configuration['autocomplete']['suggest_suffix']) {
        $autocomplete_modes[] = $this->t('Suggest word endings');
      }
      if ($this->configuration['autocomplete']['suggest_words']) {
        $autocomplete_modes[] = $this->t('Suggest additional words');
      }
      $autocomplete_modes = $autocomplete_modes ? implode('; ', $autocomplete_modes) : $this->t('none');
      $info[] = array(
        'label' => $this->t('Autocomplete suggestions'),
        'info' => $autocomplete_modes,
      );
    }

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsFeature($feature) {
    $supported = array(
      'search_api_autocomplete' => TRUE,
      'search_api_facets' => TRUE,
      'search_api_facets_operator_or' => TRUE,
    );
    return isset($supported[$feature]);
  }

  /**
   * {@inheritdoc}
   */
  public function postUpdate() {
    if (empty($this->server->original)) {
      // When in doubt, opt for the safer route and reindex.
      return TRUE;
    }
    $original_config = $this->server->original->getBackendConfig();
    $original_config += $this->defaultConfiguration();
    return $this->configuration['min_chars'] != $original_config['min_chars'];
  }

  /**
   * {@inheritdoc}
   */
  public function preDelete() {
    $schema = $this->database->schema();

    $key_value_store = $this->getKeyValueStore();
    foreach ($key_value_store->getAll() as $index_id => $db_info) {
      if ($db_info['server'] != $this->server->id()) {
        continue;
      }

      // Delete the regular field tables.
      foreach ($db_info['field_tables'] as $field) {
        if ($schema->tableExists($field['table'])) {
          $schema->dropTable($field['table']);
        }
      }

      // Delete the denormalized field tables.
      if ($schema->tableExists($db_info['index_table'])) {
        $schema->dropTable($db_info['index_table']);
      }

      $key_value_store->delete($index_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addIndex(IndexInterface $index) {
    try {
      // Create the denormalized table now.
      $index_table = $this->findFreeTable('search_api_db_', $index->id());
      $this->createFieldTable(NULL, array('table' => $index_table), 'index');

      $db_info = array();
      $db_info['server'] = $this->server->id();
      $db_info['field_tables'] = array();
      $db_info['index_table'] = $index_table;
      $this->getKeyValueStore()->set($index->id(), $db_info);

      // If there are no fields, we are done now.
      if (!$index->getFields()) {
        return;
      }
    }
    // The database operations might throw PDO or other exceptions, so we catch
    // them all and re-wrap them appropriately.
    catch (\Exception $e) {
      throw new SearchApiException($e->getMessage(), $e->getCode(), $e);
    }

    // If dealing with features or stale data or whatever, we might already have
    // settings stored for this index. If we have, we should take care to only
    // change what is needed, so we don't save the server (potentially setting
    // it to "Overridden") unnecessarily.
    // The easiest way to do this is by just pretending the index was already
    // present, but its fields were updated.
    $this->fieldsUpdated($index);
  }

  /**
   * {@inheritdoc}
   */
  public function updateIndex(IndexInterface $index) {
    // Check if any fields were updated and trigger a reindex if needed.
    if ($this->fieldsUpdated($index)) {
      $index->reindex();
    }
  }

  /**
   * Finds a free table name using a certain prefix and name base.
   *
   * Used as a helper method in fieldsUpdated().
   *
   * MySQL 5.0 imposes a 64 characters length limit for table names, PostgreSQL
   * 8.3 only allows 62 bytes. Therefore, always return a name at most 62
   * bytes long.
   *
   * @param string $prefix
   *   Prefix for the table name. Must only consist of characters valid for SQL
   *   identifiers.
   * @param string $name
   *   Name to base the table name on.
   *
   * @return string
   *   A database table name that isn't in use yet.
   */
  protected function findFreeTable($prefix, $name) {
    // A DB prefix might further reduce the maximum length of the table name.
    $maxbytes = 62;
    if ($db_prefix = $this->database->tablePrefix()) {
      // Use strlen() instead of Unicode::strlen() since we want to measure
      // bytes, not characters.
      $maxbytes -= strlen($db_prefix);
    }

    $base = $table = Unicode::truncateBytes($prefix . Unicode::strtolower(preg_replace('/[^a-z0-9]/i', '_', $name)), $maxbytes);
    $i = 0;
    while ($this->database->schema()->tableExists($table)) {
      $suffix = '_' . ++$i;
      $table = Unicode::truncateBytes($base, $maxbytes - strlen($suffix)) . $suffix;
    }
    return $table;
  }

  /**
   * Finds a free column name within a database table.
   *
   * Used as a helper method in fieldsUpdated().
   *
   * MySQL 5.0 imposes a 64 characters length limit for identifier names,
   * PostgreSQL 8.3 only allows 62 bytes. Therefore, always return a name at
   * most 62 bytes long.
   *
   * @param string $table
   *   The name of the table.
   * @param string $column
   *   The name to base the column name on.
   *
   * @return string
   *   A column name that isn't in use in the specified table yet.
   */
  protected function findFreeColumn($table, $column) {
    $maxbytes = 62;

    $base = $name = Unicode::truncateBytes(Unicode::strtolower(preg_replace('/[^a-z0-9]/i', '_', $column)), $maxbytes);
    // If the table does not exist yet, the initial name is not taken.
    if ($this->database->schema()->tableExists($table)) {
      $i = 0;
      while ($this->database->schema()->fieldExists($table, $name)) {
        $suffix = '_' . ++$i;
        $name = Unicode::truncateBytes($base, $maxbytes - strlen($suffix)) . $suffix;
      }
    }
    return $name;
  }

  /**
   * Creates or modifies a table to add an indexed field.
   *
   * Used as a helper method in fieldsUpdated().
   *
   * @param \Drupal\search_api\Item\FieldInterface|null $field
   *   The field to add. Or NULL if only the initial table with an "item_id"
   *   column should be created.
   * @param array $db
   *   Associative array containing the following:
   *   - table: The table to use for the field.
   *   - column: (optional) The column to use in that table. Defaults to
   *     "value". For creating a separate field table, it must be left empty!
   * @param string $type
   *   (optional) The type of table being created. Either "index" (for the
   *   denormalized table for an entire index) or "field" (for field-specific
   *   tables).
   */
  // @todo Write a test to ensure a field named "value" doesn't break this.
  protected function createFieldTable(FieldInterface $field = NULL, $db, $type = 'field') {
    $new_table = !$this->database->schema()->tableExists($db['table']);
    if ($new_table) {
      $table = array(
        'name' => $db['table'],
        'module' => 'search_api_db',
        'fields' => array(
          'item_id' => array(
            'type' => 'varchar',
            'length' => 50,
            'description' => 'The primary identifier of the item',
            'not null' => TRUE,
          ),
        ),
      );
      $this->database->schema()->createTable($db['table'], $table);
      $this->dbmsCompatibility->alterNewTable($db['table'], $type);
    }

    // Stop here if we want to create a table with just the 'item_id' column.
    if (!isset($field)) {
      return;
    }

    $column = isset($db['column']) ? $db['column'] : 'value';
    $db_field = $this->sqlType($field->getType());
    $db_field += array(
      'description' => "The field's value for this item",
    );
    if ($new_table) {
      $db_field['not null'] = TRUE;
    }
    $this->database->schema()->addField($db['table'], $column, $db_field);
    if ($db_field['type'] === 'varchar') {
      $index_spec = array(array($column, 10));
    }
    else {
      $index_spec = array($column);
    }
    // Create a table specification skeleton to pass to addIndex().
    $table_spec = array(
      'fields' => array(
        $column => $db_field,
      ),
      'indexes' => array(
        $column => $index_spec,
      ),
    );

    // This is a quick fix for a core bug, so we can run the tests with SQLite
    // until this is fixed.
    //
    // In SQLite, indexes and tables can't have the same name, which is
    // the case for Search API DB. We have following situation:
    // - a table named search_api_db_default_index_search_api_language
    // - a table named search_api_db_default_index
    //
    // The last table has an index on the search_api_language column,
    // which results in an index with the same as the first table, which
    // conflicts in SQLite.
    //
    // The core issue addressing this (https://www.drupal.org/node/1008128) was
    // closed as it fixed the PostgresSQL part. The SQLite fix is added in
    // https://www.drupal.org/node/2625664
    // We prevent this by adding an extra underscore (which is also the proposed
    // solution in the original core issue).
    //
    // @todo: Remove when #2625664 lands in Core. See #2625722 for a patch that
    // implements this.
    try {
      $this->database->schema()->addIndex($db['table'], '_' . $column, $index_spec, $table_spec);
    }
    catch (\PDOException $e) {
      $variables['%column'] = $column;
      $variables['%table'] = $db['table'];
      watchdog_exception('search_api_db', $e, '%type while trying to add a database index for column %column to table %table: @message in %function (line %line of %file).', $variables, RfcLogLevel::WARNING);
    }

    if ($new_table) {
      // Add a covering index for fields with multiple values.
      if (!isset($db['column'])) {
        $this->database->schema()->addPrimaryKey($db['table'], array('item_id', $column));
      }
      // This is a denormalized table with many columns, where we can't predict
      // the best covering index.
      else {
        $this->database->schema()->addPrimaryKey($db['table'], array('item_id'));
      }
    }
  }

  /**
   * Returns the schema definition for a database column for a search data type.
   *
   * @param string $type
   *   An indexed field's search type. One of the keys from
   *   \Drupal\search_api\Utility::getDefaultDataTypes().
   *
   * @return array
   *   Column configurations to use for the field's database column.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if $type is unknown.
   */
  protected function sqlType($type) {
    switch ($type) {
      case 'text':
      case 'string':
      case 'uri':
        return array('type' => 'varchar', 'length' => 255);

      case 'integer':
      case 'duration':
      case 'date':
        // 'datetime' sucks. Therefore, we just store the timestamp.
        return array('type' => 'int', 'size' => 'big');

      case 'decimal':
        return array('type' => 'float');

      case 'boolean':
        return array('type' => 'int', 'size' => 'tiny');

      default:
        throw new SearchApiException(new FormattableMarkup('Unknown field type @type. Database search module might be out of sync with Search API.', array('@type' => $type)));
    }
  }

  /**
   * Updates the storage tables when the field configuration changes.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index whose fields (might) have changed.
   *
   * @return bool
   *   TRUE if the data needs to be reindexed, FALSE otherwise.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if any exceptions occur internally, e.g., in the database
   *   layer.
   */
  protected function fieldsUpdated(IndexInterface $index) {
    try {
      $db_info = $this->getIndexDbInfo($index);
      $fields = &$db_info['field_tables'];
      $new_fields = $index->getFields();

      $reindex = FALSE;
      $cleared = FALSE;
      $text_table = NULL;
      $denormalized_table = $db_info['index_table'];

      foreach ($fields as $field_id => $field) {
        if (!isset($text_table) && Utility::isTextType($field['type'])) {
          // Stash the shared text table name for the index.
          $text_table = $field['table'];
        }

        if (!isset($new_fields[$field_id])) {
          // The field is no longer in the index, drop the data.
          $this->removeFieldStorage($field_id, $field, $denormalized_table);
          unset($fields[$field_id]);
          continue;
        }
        $old_type = $field['type'];
        $new_type = $new_fields[$field_id]->getType();
        $fields[$field_id]['type'] = $new_type;
        $fields[$field_id]['boost'] = $new_fields[$field_id]->getBoost();
        if ($old_type != $new_type) {
          if ($old_type == 'text' || $new_type == 'text') {
            // A change in fulltext status necessitates completely clearing the
            // index.
            $reindex = TRUE;
            if (!$cleared) {
              $cleared = TRUE;
              $this->deleteAllIndexItems($index);
            }
            $this->removeFieldStorage($field_id, $field, $denormalized_table);
            // Keep the table in $new_fields to create the new storage.
            continue;
          }
          elseif ($this->sqlType($old_type) != $this->sqlType($new_type)) {
            // There is a change in SQL type. We don't have to clear the index,
            // since types can be converted.
            $this->database->schema()->changeField($field['table'], 'value', 'value', $this->sqlType($new_type) + array('description' => "The field's value for this item"));
            $this->database->schema()->changeField($denormalized_table, $field['column'], $field['column'], $this->sqlType($new_type) + array('description' => "The field's value for this item"));
            $reindex = TRUE;
          }
          elseif ($old_type == 'date' || $new_type == 'date') {
            // Even though the SQL type stays the same, we have to reindex since
            // conversion rules change.
            $reindex = TRUE;
          }
        }
        elseif ($new_type == 'text' && $field['boost'] != $new_fields[$field_id]->getBoost()) {
          if (!$reindex) {
            $multiplier = $new_fields[$field_id]->getBoost() / $field['boost'];
            $this->database->update($text_table)
              ->expression('score', 'score * :mult', array(':mult' => $multiplier))
              ->condition('field_name', self::getTextFieldName($field_id))
              ->execute();
          }
        }

        // Make sure the table and column now exist. (Especially important when
        // we actually add the index for the first time.)
        $storage_exists = $this->database->schema()
          ->fieldExists($field['table'], 'value');
        $denormalized_storage_exists = $this->database->schema()
          ->fieldExists($denormalized_table, $field['column']);
        if (!Utility::isTextType($field['type']) && !$storage_exists) {
          $db = array(
            'table' => $field['table'],
          );
          $this->createFieldTable($new_fields[$field_id], $db);
        }
        // Ensure that a column is created in the denormalized storage even for
        // 'text' fields.
        if (!$denormalized_storage_exists) {
          $db = array(
            'table' => $denormalized_table,
            'column' => $field['column'],
          );
          $this->createFieldTable($new_fields[$field_id], $db, 'index');
        }
        unset($new_fields[$field_id]);
      }

      $prefix = 'search_api_db_' . $index->id();
      // These are new fields that were previously not indexed.
      foreach ($new_fields as $field_id => $field) {
        $reindex = TRUE;
        $fields[$field_id] = array();
        if (Utility::isTextType($field->getType())) {
          if (!isset($text_table)) {
            // If we have not encountered a text table, assign a name for it.
            $text_table = $this->findFreeTable($prefix . '_', 'text');
          }
          $fields[$field_id]['table'] = $text_table;
        }
        else {
          $fields[$field_id]['table'] = $this->findFreeTable($prefix . '_', $field_id);
          $this->createFieldTable($field, $fields[$field_id]);
        }

        // Always add a column in the denormalized table.
        $fields[$field_id]['column'] = $this->findFreeColumn($denormalized_table, $field_id);
        $this->createFieldTable($field, array('table' => $denormalized_table, 'column' => $fields[$field_id]['column']), 'index');

        $fields[$field_id]['type'] = $field->getType();
        $fields[$field_id]['boost'] = $field->getBoost();
      }

      // If needed, make sure the text table exists.
      if (isset($text_table) && !$this->database->schema()->tableExists($text_table)) {
        $table = array(
          'name' => $text_table,
          'module' => 'search_api_db',
          'fields' => array(
            'item_id' => array(
              'type' => 'varchar',
              'length' => 50,
              'description' => 'The primary identifier of the item',
              'not null' => TRUE,
            ),
            'field_name' => array(
              'description' => "The name of the field in which the token appears, or an MD5 hash of the field",
              'not null' => TRUE,
              'type' => 'varchar',
              'length' => 191,
            ),
            'word' => array(
              'description' => 'The text of the indexed token',
              'type' => 'varchar',
              'length' => 50,
              'not null' => TRUE,
              'binary' => TRUE,
            ),
            'score' => array(
              'description' => 'The score associated with this token',
              'type' => 'int',
              'unsigned' => TRUE,
              'not null' => TRUE,
              'default' => 0,
            ),
          ),
          'indexes' => array(
            'word_field' => array(array('word', 20), 'field_name'),
          ),
          // Add a covering index since word is not repeated for each item.
          'primary key' => array('item_id', 'field_name', 'word'),
        );
        $this->database->schema()->createTable($text_table, $table);
        $this->dbmsCompatibility->alterNewTable($text_table, 'text');
      }

      $this->getKeyValueStore()->set($index->id(), $db_info);

      return $reindex;
    }
    // The database operations might throw PDO or other exceptions, so we catch
    // them all and re-wrap them appropriately.
    catch (\Exception $e) {
      throw new SearchApiException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * Drops a field's table and its column from the denormalized table.
   *
   * @param string $name
   *   The field name.
   * @param array $field
   *   Backend-internal information about the field.
   * @param string $index_table
   *   The table which stores the denormalized data for this field.
   */
  protected function removeFieldStorage($name, $field, $index_table) {
    if (Utility::isTextType($field['type'])) {
      // Remove data from the text table.
      $this->database->delete($field['table'])
        ->condition('field_name', self::getTextFieldName($name))
        ->execute();
    }
    elseif ($this->database->schema()->tableExists($field['table'])) {
      // Remove the field table.
      $this->database->schema()->dropTable($field['table']);
    }

    // Remove the field column from the denormalized table.
    $this->database->schema()->dropField($index_table, $field['column']);
  }

  /**
   * {@inheritdoc}
   */
  public function removeIndex($index) {
    if (!is_object($index)) {
      // If the index got deleted, create a dummy to simplify the code. Since we
      // can't know, we assume the index was read-only, just to be on the safe
      // side.
      $index = Index::create(array(
        'id' => $index,
        'read_only' => TRUE,
      ));
    }

    $db_info = $this->getIndexDbInfo($index);

    try {
      if (!isset($db_info['field_tables']) && !isset($db_info['index_table'])) {
        return;
      }
      // Don't delete the index data of read-only indexes.
      if (!$index->isReadOnly()) {
        foreach ($db_info['field_tables'] as $field) {
          if ($this->database->schema()->tableExists($field['table'])) {
            $this->database->schema()->dropTable($field['table']);
          }
        }
        if ($this->database->schema()->tableExists($db_info['index_table'])) {
          $this->database->schema()->dropTable($db_info['index_table']);
        }
      }

      $this->getKeyValueStore()->delete($index->id());
    }
    // The database operations might throw PDO or other exceptions, so we catch
    // them all and re-wrap them appropriately.
    catch (\Exception $e) {
      throw new SearchApiException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function indexItems(IndexInterface $index, array $items) {
    if (!$this->getIndexDbInfo($index)) {
      throw new SearchApiException(new FormattableMarkup('No field settings for index with id @id.', array('@id' => $index->id())));
    }
    $indexed = array();
    foreach ($items as $id => $item) {
      try {
        $this->indexItem($index, $item);
        $indexed[] = $id;
      }
      catch (\Exception $e) {
        // We just log the error, hoping we can index the other items.
        $this->getLogger()->warning(Html::escape($e->getMessage()));
      }
    }
    return $indexed;
  }

  /**
   * Indexes a single item on the specified index.
   *
   * Used as a helper method in indexItems().
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index for which the item is being indexed.
   * @param \Drupal\search_api\Item\ItemInterface $item
   *   The item to index.
   *
   * @throws \Exception
   *   Any encountered database (or other) exceptions are passed on, out of this
   *   method.
   */
  protected function indexItem(IndexInterface $index, ItemInterface $item) {
    $fields = $this->getFieldInfo($index);
    $fields_updated = FALSE;
    $field_errors = array();
    $db_info = $this->getIndexDbInfo($index);
    $denormalized_table = $db_info['index_table'];
    $item_id = $item->getId();

    $transaction = $this->database->startTransaction('search_api_db_indexing');

    try {
      // Remove the item from the denormalized table.
      $this->database->delete($denormalized_table)
        ->condition('item_id', $item_id)
        ->execute();

      $denormalized_values = array();
      $text_inserts = array();
      foreach ($item->getFields() as $field_id => $field) {
        // Sometimes index changes are not triggering the update hooks
        // correctly. Therefore, to avoid DB errors, we re-check the tables
        // here before indexing.
        if (empty($fields[$field_id]['table']) && !$fields_updated) {
          unset($db_info['field_tables'][$field_id]);
          $this->fieldsUpdated($index);
          $fields_updated = TRUE;
          $fields = $db_info['field_tables'];
        }
        if (empty($fields[$field_id]['table']) && empty($field_errors[$field_id])) {
          // Log an error, but only once per field. Since a superfluous field is
          // not too serious, we just index the rest of the item normally.
          $field_errors[$field_id] = TRUE;
          $this->getLogger()->warning("Unknown field @field: please check (and re-save) the index's fields settings.", array('@field' => $field_id));
          continue;
        }

        $field_info = $fields[$field_id];
        $table = $field_info['table'];
        $column = $field_info['column'];

        $this->database->delete($table)
          ->condition('item_id', $item_id)
          ->execute();

        $type = $field->getType();
        $values = array();
        foreach ($field->getValues() as $field_value) {
          $converted_value = $this->convert($field_value, $type, $field->getOriginalType(), $index);

          // Don't add NULL values to the return array. Also, adding an empty
          // array is, of course, a waste of time.
          if (isset($converted_value) && $converted_value !== array()) {
            $values = array_merge($values, is_array($converted_value) ? $converted_value : array($converted_value));
          }
        }

        if (!$values) {
          // SQLite sometimes has problems letting columns not present in an
          // INSERT statement default to NULL, so we set NULL values for the
          // denormalized table explicitly.
          $denormalized_values[$column] = NULL;
          continue;
        }

        // If the field contains more than one value, we remember that the field
        // can be multi-valued.
        if (count($values) > 1) {
          $db_info['field_tables'][$field_id]['multi-valued'] = TRUE;
        }

        if (Utility::isTextType($type, array('text', 'tokenized_text'))) {
          // Remember the text table the first time we encounter it.
          if (!isset($text_table)) {
            $text_table = $table;
          }

          $unique_tokens = array();
          $denormalized_value = '';
          foreach ($values as $token) {
            $word = $token['value'];
            $score = $token['score'];

            // Store the first 30 characters of the string as the denormalized
            // value.
            if (strlen($denormalized_value) < 30) {
              $denormalized_value .= $word . ' ';
            }

            // Skip words that are too short, except for numbers.
            if (is_numeric($word)) {
              $word = ltrim($word, '-0');
            }
            elseif (Unicode::strlen($word) < $this->configuration['min_chars']) {
              continue;
            }

            // Taken from core search to reflect less importance of words later
            // in the text.
            // Focus is a decaying value in terms of the amount of unique words
            // up to this point. From 100 words and more, it decays, to e.g. 0.5
            // at 500 words and 0.3 at 1000 words.
            $score *= min(1, .01 + 3.5 / (2 + count($unique_tokens) * .015));

            // Only insert each canonical base form of a word once.
            $word_base_form = $this->dbmsCompatibility->preprocessIndexValue($word);

            if (!isset($unique_tokens[$word_base_form])) {
              $unique_tokens[$word_base_form] = array(
                'value' => $word,
                'score' => $score,
              );
            }
            else {
              $unique_tokens[$word_base_form]['score'] += $score;
            }
          }
          $denormalized_values[$column] = Unicode::truncateBytes(trim($denormalized_value), 30);
          if ($unique_tokens) {
            $field_name = self::getTextFieldName($field_id);
            $boost = $field_info['boost'];
            foreach ($unique_tokens as $token) {
              $text_inserts[] = array(
                'item_id' => $item_id,
                'field_name' => $field_name,
                'word' => $token['value'],
                'score' => (int) round($token['score'] * $boost * self::SCORE_MULTIPLIER),
              );
            }
          }
        }
        else {
          $denormalized_values[$column] = reset($values);

          // Make sure no duplicate values are inserted (which would lead to a
          // database exception).
          // Use the canonical base form of the value for the comparison to
          // avoid not catching different values that are duplicates under the
          // database table's collation.
          $case_insensitive_unique_values = array();
          foreach ($values as $value) {
            $value_base_form = $this->dbmsCompatibility->preprocessIndexValue("$value", 'field');
            // We still insert the value in its original case.
            $case_insensitive_unique_values[$value_base_form] = $value;
          }
          $values = array_values($case_insensitive_unique_values);

          $insert = $this->database->insert($table)
            ->fields(array('item_id', 'value'));
          foreach ($values as $value) {
            $insert->values(array(
              'item_id' => $item_id,
              'value' => $value,
            ));
          }
          $insert->execute();
        }
      }

      $this->database->insert($denormalized_table)
        ->fields(array_merge($denormalized_values, array('item_id' => $item_id)))
        ->execute();
      if ($text_inserts && isset($text_table)) {
        $query = $this->database->insert($text_table)
          ->fields(array('item_id', 'field_name', 'word', 'score'));
        foreach ($text_inserts as $row) {
          $query->values($row);
        }
        $query->execute();
      }

      // In case any new fields were detected as multi-valued, we re-save the
      // index's DB info.
      $this->getKeyValueStore()->set($index->id(), $db_info);
    }
    catch (\Exception $e) {
      $transaction->rollback();
      throw $e;
    }
  }

  /**
   * Trims long field names to fit into the text table's field_name column.
   *
   * @param string $name
   *   The field name.
   *
   * @return string
   *   The field name as stored in the field_name column.
   */
  protected static function getTextFieldName($name) {
    if (strlen($name) > 191) {
      // Replace long field names with something unique and predictable.
      return md5($name);
    }
    else {
      return $name;
    }
  }

  /**
   * Converts a value between two search types.
   *
   * @param mixed $value
   *   The value to convert.
   * @param string $type
   *   The type to convert to. One of the keys from
   *   search_api_default_field_types().
   * @param string $original_type
   *   The value's original type.
   * @param \Drupal\search_api\IndexInterface $index
   *   The index for which this conversion takes place.
   *
   * @return mixed
   *   The converted value.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if $type is unknown.
   */
  protected function convert($value, $type, $original_type, IndexInterface $index) {
    if (!isset($value)) {
      // For text fields, we have to return an array even if the value is NULL.
      return Utility::isTextType($type, array('text', 'tokenized_text')) ? array() : NULL;
    }
    switch ($type) {
      case 'text':
        // For dates, splitting the timestamp makes no sense.
        if ($original_type == 'date') {
          $value = $this->getDateFormatter()->format($value, 'custom', 'Y y F M n m j d l D');
        }
        $ret = array();
        foreach (preg_split('/[^\p{L}\p{N}]+/u', $value, -1, PREG_SPLIT_NO_EMPTY) as $v) {
          if ($v) {
            if (strlen($v) > 50) {
              $this->getLogger()->warning('An overlong word (more than 50 characters) was encountered while indexing: %word.<br />Database search servers currently cannot index such words correctly – the word was therefore trimmed to the allowed length. Ensure you are using a tokenizer preprocessor.', array('%word' => $v));
              $v = Unicode::truncateBytes($v, 50);
            }
            $ret[] = array(
              'value' => $v,
              'score' => 1,
            );
          }
        }
        // This used to fall through the tokenized case.
        return $ret;

      case 'tokenized_text':
        while (TRUE) {
          foreach ($value as $i => $v) {
            // Check for over-long tokens.
            $score = $v['score'];
            $v = $v['value'];
            if (strlen($v) > 50) {
              $words = preg_split('/[^\p{L}\p{N}]+/u', $v, -1, PREG_SPLIT_NO_EMPTY);
              if (count($words) > 1 && max(array_map('strlen', $words)) <= 50) {
                // Overlong token is due to bad tokenizing.
                // Check for "Tokenizer" preprocessor on index.
                if (empty($index->getProcessors()['tokenizer'])) {
                  $this->getLogger()->warning('An overlong word (more than 50 characters) was encountered while indexing, due to bad tokenizing. It is recommended to enable the "Tokenizer" preprocessor for indexes using database servers. Otherwise, the backend class has to use its own, fixed tokenizing.');
                }
                else {
                  $this->getLogger()->warning('An overlong word (more than 50 characters) was encountered while indexing, due to bad tokenizing. Please check your settings for the "Tokenizer" preprocessor to ensure that data is tokenized correctly.');
                }
              }

              $tokens = array();
              foreach ($words as $word) {
                if (strlen($word) > 50) {
                  $this->getLogger()->warning('An overlong word (more than 50 characters) was encountered while indexing: %word.<br />Database search servers currently cannot index such words correctly – the word was therefore trimmed to the allowed length.', array('%word' => $word));
                  $word = Unicode::truncateBytes($word, 50);
                }
                $tokens[] = array(
                  'value' => $word,
                  'score' => $score,
                );
              }
              array_splice($value, $i, 1, $tokens);
              // Restart the loop looking through all the tokens.
              continue 2;
            }
          }
          break;
        }
        return $value;

      case 'string':
      case 'uri':
        // For non-dates, PHP can handle this well enough.
        if ($original_type == 'date') {
          return date('c', $value);
        }
        if (strlen($value) > 255) {
          $value = Unicode::truncateBytes($value, 255);
          $this->getLogger()->warning('An overlong value (more than 255 characters) was encountered while indexing: %value.<br />Database search servers currently cannot index such values correctly – the value was therefore trimmed to the allowed length.', array('%value' => $value));
        }
        return $value;

      case 'integer':
      case 'duration':
      case 'decimal':
        return 0 + $value;

      case 'boolean':
        return $value ? 1 : 0;

      case 'date':
        if (is_numeric($value) || !$value) {
          return 0 + $value;
        }
        return strtotime($value);

      default:
        throw new SearchApiException(new FormattableMarkup('Unknown field type @type. Database search module might be out of sync with Search API.', array('@type' => $type)));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItems(IndexInterface $index, array $item_ids) {
    try {
      $db_info = $this->getIndexDbInfo($index);

      if (empty($db_info['field_tables'])) {
        return;
      }
      foreach ($db_info['field_tables'] as $field) {
        $this->database->delete($field['table'])
          ->condition('item_id', $item_ids, 'IN')
          ->execute();
      }
      // Delete the denormalized field data.
      $this->database->delete($db_info['index_table'])
        ->condition('item_id', $item_ids, 'IN')
        ->execute();
    }
    catch (\Exception $e) {
      // The database operations might throw PDO or other exceptions, so we
      // catch them all and re-wrap them appropriately.
      throw new SearchApiException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAllIndexItems(IndexInterface $index) {
    try {
      $db_info = $this->getIndexDbInfo($index);

      foreach ($db_info['field_tables'] as $field_id => $field) {
        $this->database->truncate($field['table'])->execute();
        unset($db_info['field_tables'][$field_id]['multi-valued']);
      }
      $this->getKeyValueStore()->set($index->id(), $db_info);

      $this->database->truncate($db_info['index_table'])->execute();
    }
    catch (\Exception $e) {
      // The database operations might throw PDO or other exceptions, so we
      // catch them all and re-wrap them appropriately.
      throw new SearchApiException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function search(QueryInterface $query) {
    $this->ignored = $this->warnings = array();
    $index = $query->getIndex();
    $db_info = $this->getIndexDbInfo($index);

    if (!isset($db_info['field_tables'])) {
      throw new SearchApiException(new FormattableMarkup('Unknown index @id.', array('@id' => $index->id())));
    }
    $fields = $this->getFieldInfo($index);

    $db_query = $this->createDbQuery($query, $fields);

    $results = Utility::createSearchResultSet($query);

    $skip_count = $query->getOption('skip result count');
    if (!$skip_count) {
      $count_query = $db_query->countQuery();
      $results->setResultCount($count_query->execute()->fetchField());
    }

    if ($skip_count || $results->getResultCount()) {
      if ($query->getOption('search_api_facets')) {
        $results->setExtraData('search_api_facets', $this->getFacets($query, clone $db_query));
      }

      $query_options = $query->getOptions();
      if (isset($query_options['offset']) || isset($query_options['limit'])) {
        $offset = isset($query_options['offset']) ? $query_options['offset'] : 0;
        $limit = isset($query_options['limit']) ? $query_options['limit'] : 1000000;
        $db_query->range($offset, $limit);
      }

      $sort = $query->getSorts();
      if ($sort) {
        foreach ($sort as $field_name => $order) {
          if ($order != QueryInterface::SORT_ASC && $order != QueryInterface::SORT_DESC) {
            $msg = $this->t('Unknown sort order @order. Assuming "@default".', array('@order' => $order, '@default' => QueryInterface::SORT_ASC));
            $this->warnings[(string) $msg] = 1;
            $order = QueryInterface::SORT_ASC;
          }
          if ($field_name == 'search_api_relevance') {
            $db_query->orderBy('score', $order);
            continue;
          }
          if ($field_name == 'search_api_id') {
            $db_query->orderBy('item_id', $order);
            continue;
          }

          if (!isset($fields[$field_name])) {
            throw new SearchApiException(new FormattableMarkup('Trying to sort on unknown field @field.', array('@field' => $field_name)));
          }
          $alias = $this->getTableAlias(array('table' => $db_info['index_table']), $db_query);
          $db_query->orderBy($alias . '.' . $fields[$field_name]['column'], $order);
          // PostgreSQL automatically adds a field to the SELECT list when
          // sorting on it. Therefore, if we have aggregations present we also
          // have to add the field to the GROUP BY (since Drupal won't do it for
          // us). However, if no aggregations are present, a GROUP BY would lead
          // to another error. Therefore, we only add it if there is already a
          // GROUP BY.
          if ($db_query->getGroupBy()) {
            $db_query->groupBy($alias . '.' . $fields[$field_name]['column']);
          }
        }
      }
      else {
        $db_query->orderBy('score', 'DESC');
      }

      $result = $db_query->execute();

      foreach ($result as $row) {
        $item = Utility::createItem($index, $row->item_id);
        $item->setScore($row->score / self::SCORE_MULTIPLIER);
        $results->addResultItem($item);
      }
      if ($skip_count && !empty($item)) {
        $results->setResultCount(1);
      }
    }

    $results->setWarnings(array_keys($this->warnings));
    $results->setIgnoredSearchKeys(array_keys($this->ignored));

    return $results;
  }

  /**
   * Creates a database query for a search.
   *
   * Used as a helper method in search() and getAutocompleteSuggestions().
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The search query for which to create the database query.
   * @param array $fields
   *   The internal field information to use.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   A database query object which will return the appropriate results (except
   *   for the range and sorting) for the given search query.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if some illegal query setting (unknown field, etc.) was
   *   encountered.
   */
  protected function createDbQuery(QueryInterface $query, array $fields) {
    $keys = &$query->getKeys();
    $keys_set = (boolean) $keys;
    $keys = $this->prepareKeys($keys);

    // Only filter by fulltext keys if there are any real keys present.
    if ($keys && (!is_array($keys) || count($keys) > 2 || (!isset($keys['#negation']) && count($keys) > 1))) {
      // Special case: if the outermost $keys array has "#negation" set, we
      // can't handle it like other negated subkeys. To avoid additional
      // complexity later, we just wrap $keys so it becomes a subkey.
      if (!empty($keys['#negation'])) {
        $keys = array(
          '#conjunction' => 'AND',
          $keys,
        );
      }

      $fulltext_fields = $this->getQueryFulltextFields($query);
      if ($fulltext_fields) {
        $fulltext_field_information = array();
        foreach ($fulltext_fields as $name) {
          if (!isset($fields[$name])) {
            throw new SearchApiException(new FormattableMarkup('Unknown field @field specified as search target.', array('@field' => $name)));
          }
          if (!Utility::isTextType($fields[$name]['type'])) {
            $types = $this->getDataTypePluginManager()->getInstances();
            $type = $types[$fields[$name]['type']]->label();
            throw new SearchApiException(new FormattableMarkup('Cannot perform fulltext search on field @field of type @type.', array('@field' => $name, '@type' => $type)));
          }
          $fulltext_field_information[$name] = $fields[$name];
        }

        $db_query = $this->createKeysQuery($keys, $fulltext_field_information, $fields, $query->getIndex());
      }
      else {
        $this->getLogger()->warning('Search keys are given but no fulltext fields are defined.');
        $msg = $this->t('Search keys are given but no fulltext fields are defined.');
        $this->warnings[(string) $msg] = 1;
      }
    }
    elseif ($keys_set) {
      $msg = $this->t('No valid search keys were present in the query.');
      $this->warnings[(string) $msg] = 1;
    }

    if (!isset($db_query)) {
      $db_info = $this->getIndexDbInfo($query->getIndex());
      $db_query = $this->database->select($db_info['index_table'], 't');
      $db_query->addField('t', 'item_id', 'item_id');
      $db_query->addExpression(':score', 'score', array(':score' => self::SCORE_MULTIPLIER));
      $db_query->distinct();
    }

    $condition_group = $query->getConditionGroup();
    if ($condition_group->getConditions()) {
      $condition = $this->createDbCondition($condition_group, $fields, $db_query, $query->getIndex());
      if ($condition) {
        $db_query->condition($condition);
      }
    }

    $db_query->addTag('search_api_db_search');
    $db_query->addMetaData('search_api_query', $query);
    $db_query->addMetaData('search_api_db_fields', $fields);

    return $db_query;
  }

  /**
   * Removes nested expressions and phrase groupings from the search keys.
   *
   * Used as a helper method in createDbQuery() and createDbCondition().
   *
   * @param array|string|null $keys
   *   The keys which should be preprocessed.
   *
   * @return array|string|null
   *   The preprocessed keys.
   */
  protected function prepareKeys($keys) {
    if (is_scalar($keys)) {
      $keys = $this->splitKeys($keys);
      return is_array($keys) ? $this->eliminateDuplicates($keys) : $keys;
    }
    elseif (!$keys) {
      return NULL;
    }
    $keys = $this->eliminateDuplicates($this->splitKeys($keys));
    $conj = $keys['#conjunction'];
    $neg = !empty($keys['#negation']);
    foreach ($keys as $i => &$nested) {
      if (is_array($nested)) {
        $nested = $this->prepareKeys($nested);
        if (is_array($nested) && $neg == !empty($nested['#negation'])) {
          if ($nested['#conjunction'] == $conj) {
            unset($nested['#conjunction'], $nested['#negation']);
            foreach ($nested as $renested) {
              $keys[] = $renested;
            }
            unset($keys[$i]);
          }
        }
      }
    }
    $keys = array_filter($keys);
    if (($count = count($keys)) <= 2) {
      if ($count < 2 || isset($keys['#negation'])) {
        $keys = NULL;
      }
      else {
        unset($keys['#conjunction']);
        $keys = reset($keys);
      }
    }
    return $keys;
  }

  /**
   * Splits a keyword expression into separate words.
   *
   * Used as a helper method in prepareKeys().
   *
   * @param array|string $keys
   *   The keys to split.
   *
   * @return array|string|null
   *   The keys split into separate words.
   */
  protected function splitKeys($keys) {
    if (is_scalar($keys)) {
      $processed_keys = $this->dbmsCompatibility->preprocessIndexValue(trim($keys));
      if (is_numeric($processed_keys)) {
        return ltrim($processed_keys, '-0');
      }
      elseif (Unicode::strlen($processed_keys) < $this->configuration['min_chars']) {
        $this->ignored[$keys] = 1;
        return NULL;
      }
      $words = preg_split('/[^\p{L}\p{N}]+/u', $processed_keys, -1, PREG_SPLIT_NO_EMPTY);
      if (count($words) > 1) {
        $processed_keys = $this->splitKeys($words);
        if ($processed_keys) {
          $processed_keys['#conjunction'] = 'AND';
        }
        else {
          $processed_keys = NULL;
        }
      }
      return $processed_keys;
    }
    foreach ($keys as $i => $key) {
      if (Element::child($i)) {
        $keys[$i] = $this->splitKeys($key);
      }
    }
    return array_filter($keys);
  }

  /**
   * Eliminates duplicate keys from a keyword array.
   *
   * Used as a helper method in prepareKeys().
   *
   * @param array $keys
   *   The keywords to parse.
   * @param array $words
   *   (optional) A cache of all encountered words so far. Used internally for
   *   recursive invocations.
   *
   * @return array
   *   The processed keywords.
   */
  protected function eliminateDuplicates($keys, &$words = array()) {
    foreach ($keys as $i => $word) {
      if (!Element::child($i)) {
        continue;
      }
      if (is_scalar($word)) {
        if (isset($words[$word])) {
          unset($keys[$i]);
        }
        else {
          $words[$word] = TRUE;
        }
      }
      else {
        $keys[$i] = $this->eliminateDuplicates($word, $words);
      }
    }
    return $keys;
  }

  /**
   * Creates a SELECT query for given search keys.
   *
   * Used as a helper method in createDbQuery() and createDbCondition().
   *
   * @param string|array $keys
   *   The search keys, formatted like the return value of
   *   \Drupal\search_api\Query\QueryInterface::getKeys(), but preprocessed
   *   according to internal requirements.
   * @param array $fields
   *   The fulltext fields on which to search, with their names as keys mapped
   *   to internal information about them.
   * @param array $all_fields
   *   Internal information about all indexed fields on the index.
   * @param \Drupal\search_api\IndexInterface $index
   *   The index we're searching on.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   A SELECT query returning item_id and score (or only item_id, if
   *   $keys['#negation'] is set).
   */
  protected function createKeysQuery($keys, array $fields, array $all_fields, IndexInterface $index) {
    if (!is_array($keys)) {
      $keys = array(
        '#conjunction' => 'AND',
        $keys,
      );
    }

    $neg = !empty($keys['#negation']);
    $conj = $keys['#conjunction'];
    $words = array();
    $nested = array();
    $negated = array();
    $db_query = NULL;
    $mul_words = FALSE;
    $neg_nested = $neg && $conj == 'AND';
    $match_parts = !empty($this->configuration['partial_matches']);
    $keyword_hits = array();

    foreach ($keys as $i => $key) {
      if (!Element::child($i)) {
        continue;
      }
      if (is_scalar($key)) {
        $words[] = $key;
      }
      elseif (empty($key['#negation'])) {
        if ($neg) {
          // If this query is negated, we also only need item IDs from
          // subqueries.
          $key['#negation'] = TRUE;
        }
        $nested[] = $key;
      }
      else {
        $negated[] = $key;
      }
    }
    $word_count = count($words);
    $subs = $word_count + count($nested);
    $not_nested = ($subs <= 1 && count($fields) == 1) || ($neg && $conj == 'OR' && !$negated);

    if ($words) {
      // All text fields in the index share a table. Get name from the first.
      $field = reset($fields);
      $db_query = $this->database->select($field['table'], 't');
      $mul_words = ($word_count > 1);
      if ($neg_nested) {
        $db_query->fields('t', array('item_id', 'word'));
      }
      elseif ($neg) {
        $db_query->fields('t', array('item_id'));
      }
      elseif ($not_nested) {
        $db_query->fields('t', array('item_id', 'score'));
      }
      else {
        $db_query->fields('t', array('item_id', 'score', 'word'));
      }

      if (!$match_parts) {
        $db_query->condition('word', $words, 'IN');
      }
      else {
        $db_or = new Condition('OR');
        // GROUP BY all existing non-grouped, non-aggregated columns – except
        // "word", which we remove since it will be useless to us in this case.
        $columns = &$db_query->getFields();
        unset($columns['word']);
        foreach (array_keys($columns) as $column) {
          $db_query->groupBy($column);
        }

        foreach ($words as $i => $word) {
          $db_or->condition('t.word', '%' . $this->database->escapeLike($word) . '%', 'LIKE');

          // Add an expression for each keyword that shows whether the indexed
          // word matches that particular keyword. That way we don't return a
          // result multiple times if a single indexed word (partially) matches
          // multiple keywords. We also remember the column name so we can
          // afterwards verify that each word matched at least once.
          $alias = 'w' . $i;
          $alias = $db_query->addExpression("t.word LIKE '%" . $this->database->escapeLike($word) . "%'", $alias);
          $db_query->groupBy($alias);
          $keyword_hits[] = $alias;
        }
        // Also add expressions for any nested queries.
        for ($i = $word_count; $i < $subs; ++$i) {
          $alias = 'w' . $i;
          $alias = $db_query->addExpression('0', $alias);
          $db_query->groupBy($alias);
          $keyword_hits[] = $alias;
        }
        $db_query->condition($db_or);
      }

      $db_query->condition('field_name', array_map(array(__CLASS__, 'getTextFieldName'), array_keys($fields)), 'IN');
    }

    if ($nested) {
      $word = '';
      foreach ($nested as $i => $k) {
        $query = $this->createKeysQuery($k, $fields, $all_fields, $index);
        if (!$neg) {
          if (!$match_parts) {
            $word .= ' ';
            $var = ':word' . strlen($word);
            $query->addExpression($var, 'word', array($var => $word));
          }
          else {
            $i += $word_count;
            for ($j = 0; $j < $subs; ++$j) {
              $alias = isset($keyword_hits[$j]) ? $keyword_hits[$j] : "w$j";
              $keyword_hits[$j] = $query->addExpression($i == $j ? '1' : '0', $alias);
            }
          }
        }
        if (!isset($db_query)) {
          $db_query = $query;
        }
        elseif ($not_nested) {
          $db_query->union($query, 'UNION');
        }
        else {
          $db_query->union($query, 'UNION ALL');
        }
      }
    }

    if (isset($db_query) && !$not_nested) {
      $db_query = $this->database->select($db_query, 't');
      $db_query->addField('t', 'item_id', 'item_id');
      if (!$neg) {
        $db_query->addExpression('SUM(t.score)', 'score');
        $db_query->groupBy('t.item_id');
      }
      if ($conj == 'AND' && $subs > 1) {
        $var = ':subs' . ((int) $subs);
        if (!$db_query->getGroupBy()) {
          $db_query->groupBy('t.item_id');
        }
        if (!$match_parts) {
          if ($mul_words) {
            $db_query->having('COUNT(DISTINCT t.word) >= ' . $var, array($var => $subs));
          }
          else {
            $db_query->having('COUNT(t.word) >= ' . $var, array($var => $subs));
          }
        }
        else {
          foreach ($keyword_hits as $alias) {
            $db_query->having("SUM($alias) >= 1");
          }
        }
      }
    }

    if ($negated) {
      if (!isset($db_query) || $conj == 'OR') {
        if (isset($db_query)) {
          // We are in a rather bizarre case where the keys are something like
          // "a OR (NOT b)".
          $old_query = $db_query;
        }

        // We use this table because all items should be contained exactly once.
        $db_info = $this->getIndexDbInfo($index);
        $db_query = $this->database->select($db_info['index_table'], 't');
        $db_query->addField('t', 'item_id', 'item_id');
        if (!$neg) {
          $db_query->addExpression(':score', 'score', array(':score' => self::SCORE_MULTIPLIER));
          $db_query->distinct();
        }
      }

      if ($conj == 'AND') {
        foreach ($negated as $k) {
          $db_query->condition('t.item_id', $this->createKeysQuery($k, $fields, $all_fields, $index), 'NOT IN');
        }
      }
      else {
        $or = new Condition('OR');
        foreach ($negated as $k) {
          $or->condition('t.item_id', $this->createKeysQuery($k, $fields, $all_fields, $index), 'NOT IN');
        }
        if (isset($old_query)) {
          $or->condition('t.item_id', $old_query, 'NOT IN');
        }
        $db_query->condition($or);
      }
    }

    if ($neg_nested) {
      $db_query = $this->database->select($db_query, 't')->fields('t', array('item_id'));
    }

    return $db_query;
  }

  /**
   * Creates a database query condition for a given search filter.
   *
   * Used as a helper method in createDbQuery().
   *
   * @param \Drupal\search_api\Query\ConditionGroupInterface $conditions
   *   The conditions for which a condition should be created.
   * @param array $fields
   *   Internal information about the index's fields.
   * @param \Drupal\Core\Database\Query\SelectInterface $db_query
   *   The database query to which the condition will be added.
   * @param \Drupal\search_api\IndexInterface $index
   *   The index we're searching on.
   *
   * @return \Drupal\Core\Database\Query\ConditionInterface|null
   *   The condition to set on the query, or NULL if none is necessary.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an unknown field was used in the filter.
   */
  protected function createDbCondition(ConditionGroupInterface $conditions, array $fields, SelectInterface $db_query, IndexInterface $index) {
    $conjunction = $conditions->getConjunction();
    $db_condition = new Condition($conjunction);
    $db_info = $this->getIndexDbInfo($index);

    // Store the table aliases for the fields in this condition group.
    $tables = array();
    $wildcard_count = 0;
    foreach ($conditions->getConditions() as $condition) {
      if ($condition instanceof ConditionGroupInterface) {
        $sub_condition = $this->createDbCondition($condition, $fields, $db_query, $index);
        if ($sub_condition) {
          $db_condition->condition($sub_condition);
        }
      }
      else {
        $field = $condition->getField();
        $operator = $condition->getOperator();
        $value = $condition->getValue();
        $not_equals = in_array($operator, array('<>', '!=', 'NOT IN'));

        // We don't index the datasource explicitly, so this needs a bit of
        // magic.
        // @todo Index the datasource explicitly so this doesn't need magic.
        if ($field === 'search_api_datasource') {
          if (empty($tables[NULL])) {
            $table = array(
              'table' => $db_info['index_table'],
            );
            $tables[NULL] = $this->getTableAlias($table, $db_query);
          }
          $operator = $not_equals ? 'NOT LIKE' : 'LIKE';
          $prefix = Utility::createCombinedId($value, '');
          $db_condition->condition($tables[NULL] . '.item_id', $this->database->escapeLike($prefix) . '%', $operator);
          continue;
        }
        if (!isset($fields[$field])) {
          throw new SearchApiException(new FormattableMarkup('Unknown field in filter clause: @field.', array('@field' => $field)));
        }
        $field_info = $fields[$field];
        // For NULL values, we can just use the single-values table, since we
        // only need to know if there's any value at all for that field.
        if ($value === NULL || empty($field_info['multi-valued'])) {
          if (empty($tables[NULL])) {
            $table = array('table' => $db_info['index_table']);
            $tables[NULL] = $this->getTableAlias($table, $db_query);
          }
          $column = $tables[NULL] . '.' . $field_info['column'];
          if ($value === NULL) {
            $method = $not_equals ? 'isNotNull' : 'isNull';
            $db_condition->$method($column);
          }
          else {
            $db_condition->condition($column, $value, $operator);
          }
        }
        elseif (Utility::isTextType($field_info['type'])) {
          $keys = $this->prepareKeys($value);
          if (!isset($keys)) {
            continue;
          }
          $query = $this->createKeysQuery($keys, array($field => $field_info), $fields, $index);
          // We only want the item IDs, so we use the keys query as a nested
          // query.
          $query = $this->database->select($query, 't')
            ->fields('t', array('item_id'));
          $db_condition->condition('t.item_id', $query, $not_equals ? 'NOT IN' : 'IN');
        }
        elseif ($not_equals) {
          // The situation is more complicated for negative conditions on
          // multi-valued fields, since we must make sure that results are
          // excluded if ANY of the field's values equals the one(s) given in
          // this condition. Probably the most performant way to do this is to
          // do a LEFT JOIN with a positive filter on the excluded values in the
          // ON clause and then make sure we have no value for the field.
          $wildcard = ':values_' . ++$wildcard_count . '[]';
          $arguments = array($wildcard => (array) $value);
          $additional_on = "%alias.value IN ($wildcard)";
          $alias = $this->getTableAlias($field_info, $db_query, TRUE, 'leftJoin', $additional_on, $arguments);
          $db_condition->isNull($alias . '.value');
        }
        else {
          // We need to join the table if it hasn't been joined (for this
          // condition group) before, or if we have "AND" as the active
          // conjunction.
          if ($conjunction == 'AND' || empty($tables[$field])) {
            $tables[$field] = $this->getTableAlias($field_info, $db_query, TRUE);
          }
          $column = $tables[$field] . '.value';
          $db_condition->condition($column, $value, $operator);
        }
      }
    }
    return $db_condition->count() ? $db_condition : NULL;
  }

  /**
   * Joins a field's table into a database select query.
   *
   * @param array $field
   *   The field information array. The "table" key should contain the table
   *   name to which a join should be made.
   * @param \Drupal\Core\Database\Query\SelectInterface $db_query
   *   The database query used.
   * @param bool $new_join
   *   (optional) If TRUE, a join is done even if the table was already joined
   *   to in the query.
   * @param string $join
   *   (optional) The join method to use. Must be a method of the $db_query.
   *   Normally, "join", "innerJoin", "leftJoin" and "rightJoin" are supported.
   * @param string|null $additional_on
   *   (optional) If given, an SQL string with additional conditions for the ON
   *   clause of the join.
   * @param array $on_arguments
   *   (optional) Additional arguments for the ON clause.
   *
   * @return string
   *   The alias for the field's table.
   */
  protected function getTableAlias(array $field, SelectInterface $db_query, $new_join = FALSE, $join = 'leftJoin', $additional_on = NULL, array $on_arguments = array()) {
    if (!$new_join) {
      foreach ($db_query->getTables() as $alias => $info) {
        $table = $info['table'];
        if (is_scalar($table) && $table == $field['table']) {
          return $alias;
        }
      }
    }
    $condition = 't.item_id = %alias.item_id';
    if ($additional_on) {
      $condition .= ' AND ' . $additional_on;
    }
    return $db_query->$join($field['table'], 't', $condition, $on_arguments);
  }

  /**
   * Computes facets for a search query.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The search query for which facets should be computed.
   * @param \Drupal\Core\Database\Query\SelectInterface $db_query
   *   A database select query which returns all results of that search query.
   *
   * @return array
   *   An array of facets, as specified by the search_api_facets feature.
   */
  protected function getFacets(QueryInterface $query, SelectInterface $db_query) {
    $table = $this->getTemporaryResultsTable($db_query);
    if (!$table) {
      return array();
    }

    $fields = $this->getFieldInfo($query->getIndex());
    $ret = array();
    foreach ($query->getOption('search_api_facets') as $key => $facet) {
      if (empty($fields[$facet['field']])) {
        $msg = $this->t('Unknown facet field @field.', array('@field' => $facet['field']));
        $this->warnings[(string) $msg] = 1;
        continue;
      }
      $field = $fields[$facet['field']];

      if (empty($facet['operator']) || $facet['operator'] != 'or') {
        // All the AND facets can use the main query.
        $select = $this->database->select($table, 't');
      }
      else {
        // For OR facets, we need to build a different base query that excludes
        // the facet filters applied to the facet.
        $or_query = clone $query;
        $conditions = &$or_query->getConditionGroup()->getConditions();
        $tag = 'facet:' . $facet['field'];
        foreach ($conditions as $i => $condition) {
          if ($condition instanceof ConditionGroupInterface && $condition->hasTag($tag)) {
            unset($conditions[$i]);
          }
        }
        $or_db_query = $this->createDbQuery($or_query, $fields);
        $select = $this->database->select($or_db_query, 't');
      }

      // If "Include missing facet" is disabled, we use an INNER JOIN and add IS
      // NOT NULL for shared tables.
      $is_text_type = Utility::isTextType($field['type']);
      $alias = $this->getTableAlias($field, $select, TRUE, $facet['missing'] ? 'leftJoin' : 'innerJoin');
      $select->addField($alias, $is_text_type ? 'word' : 'value', 'value');
      if ($is_text_type) {
        $select->condition($alias . '.field_name', $this->getTextFieldName($facet['field']));
      }
      if (!$facet['missing'] && !$is_text_type) {
        $select->isNotNull($alias . '.value');
      }
      $select->addExpression('COUNT(DISTINCT t.item_id)', 'num');
      $select->groupBy('value');
      $select->orderBy('num', 'DESC');

      $limit = $facet['limit'];
      if ((int) $limit > 0) {
        $select->range(0, $limit);
      }
      if ($facet['min_count'] > 1) {
        $select->having('num >= :count', array(':count' => $facet['min_count']));
      }

      $terms = array();
      $values = array();
      $has_missing = FALSE;
      foreach ($select->execute() as $row) {
        $terms[] = array(
          'count' => $row->num,
          'filter' => isset($row->value) ? '"' . $row->value . '"' : '!',
        );
        if (isset($row->value)) {
          $values[] = $row->value;
        }
        else {
          $has_missing = TRUE;
        }
      }

      // If 'Minimum facet count' is set to 0 in the display options for this
      // facet, we need to retrieve all facets, even ones that aren't matched in
      // our search result set above. Here we SELECT all DISTINCT facets, and
      // add in those facets that weren't added above.
      if ($facet['min_count'] < 1) {
        $select = $this->database->select($field['table'], 't');
        $select->addField('t', 'value', 'value');
        $select->distinct();
        if ($values) {
          $select->condition('value', $values, 'NOT IN');
        }
        $select->isNotNull('value');
        foreach ($select->execute() as $row) {
          $terms[] = array(
            'count' => 0,
            'filter' => '"' . $row->value . '"',
          );
        }
        if ($facet['missing'] && !$has_missing) {
          $terms[] = array(
            'count' => 0,
            'filter' => '!',
          );
        }
      }

      $ret[$key] = $terms;
    }
    return $ret;
  }

  /**
   * Creates a temporary table from a select query.
   *
   * Will return the name of a table containing the item IDs of all results, or
   * FALSE on failure.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $db_query
   *   The select query whose results should be stored in the temporary table.
   *
   * @return string|false
   *   The name of the temporary table, or FALSE on failure.
   */
  protected function getTemporaryResultsTable(SelectInterface $db_query) {
    // We only need the id field, not the score.
    $fields = &$db_query->getFields();
    unset($fields['score']);
    if (count($fields) != 1 || !isset($fields['item_id'])) {
      $this->getLogger()->warning('Error while adding facets: only "item_id" field should be used, used are: @fields.', array('@fields' => implode(', ', array_keys($fields))));
      return FALSE;
    }
    $expressions = &$db_query->getExpressions();
    $expressions = array();

    // If there's a GROUP BY for item_id, we leave that, all others need to be
    // discarded.
    $group_by = &$db_query->getGroupBy();
    $group_by = array_intersect_key($group_by, array('t.item_id' => TRUE));

    $db_query->distinct();
    if (!$db_query->preExecute()) {
      return FALSE;
    }
    $args = $db_query->getArguments();
    try {
      $result = $this->database->queryTemporary((string) $db_query, $args);
    }
    catch (\PDOException $e) {
      watchdog_exception('search_api', $e, '%type while trying to create a temporary table: @message in %function (line %line of %file).');
      return FALSE;
    }
    return $result;
  }

  /**
   * Implements SearchApiAutocompleteInterface::getAutocompleteSuggestions().
   */
  public function getAutocompleteSuggestions(QueryInterface $query, SearchApiAutocompleteSearch $search, $incomplete_key, $user_input) {
    $settings = isset($this->configuration['autocomplete']) ? $this->configuration['autocomplete'] : array();
    $settings += array(
      'suggest_suffix' => TRUE,
      'suggest_words' => TRUE,
    );
    // If none of these options is checked, the user apparently chose a very
    // roundabout way of telling us he doesn't want autocompletion.
    if (!array_filter($settings)) {
      return array();
    }

    $index = $query->getIndex();
    $db_info = $this->getIndexDbInfo($index);
    if (empty($db_info['field_tables'])) {
      throw new SearchApiException(new FormattableMarkup('Unknown index @id.', array('@id' => $index->id())));
    }
    $fields = $this->getFieldInfo($index);

    $suggestions = array();
    $passes = array();
    $incomplete_like = NULL;

    // Make the input lowercase as the indexed data is (usually) also all
    // lowercase.
    $incomplete_key = Unicode::strtolower($incomplete_key);
    $user_input = Unicode::strtolower($user_input);

    // Decide which methods we want to use.
    if ($incomplete_key && $settings['suggest_suffix']) {
      $passes[] = 1;
      $incomplete_like = $this->database->escapeLike($incomplete_key) . '%';
    }
    if ($settings['suggest_words'] && (!$incomplete_key || strlen($incomplete_key) >= $this->configuration['min_chars'])) {
      $passes[] = 2;
    }

    if (!$passes) {
      return array();
    }

    // We want about half of the suggestions from each enabled method.
    $limit = $query->getOption('limit', 10);
    $limit /= count($passes);
    $limit = ceil($limit);

    // Also collect all keywords already contained in the query so we don't
    // suggest them.
    $keys = preg_split('/[^\p{L}\p{N}]+/u', $user_input, -1, PREG_SPLIT_NO_EMPTY);
    $keys = array_combine($keys, $keys);
    if ($incomplete_key) {
      $keys[$incomplete_key] = $incomplete_key;
    }

    foreach ($passes as $pass) {
      if ($pass == 2 && $incomplete_key) {
        $query->keys($user_input);
      }
      // To avoid suggesting incomplete words, we have to temporarily disable
      // the "partial_matches" option. There should be no way we'll save the
      // server during the createDbQuery() call, so this should be safe.
      $options = $this->options;
      $this->options['partial_matches'] = FALSE;
      $db_query = $this->createDbQuery($query, $fields);
      $this->options = $options;

      // We need a list of all current results to match the suggestions against.
      // However, since MySQL doesn't allow using a temporary table multiple
      // times in one query, we regrettably have to do it this way.
      $fulltext_fields = $this->getQueryFulltextFields($query);
      if (count($fulltext_fields) > 1) {
        $all_results = $db_query->execute()->fetchCol();
        // Compute the total number of results so we can later sort out matches
        // that occur too often.
        $total = count($all_results);
      }
      else {
        $table = $this->getTemporaryResultsTable($db_query);
        if (!$table) {
          return array();
        }
        $all_results = $this->database->select($table, 't')
          ->fields('t', array('item_id'));
        $total = $this->database->query("SELECT COUNT(item_id) FROM {{$table}}")->fetchField();
      }
      $max_occurrences = $this->getConfigFactory()->get('search_api_db.settings')->get('autocomplete_max_occurrences');
      $max_occurrences = max(1, floor($total * $max_occurrences));

      if (!$total) {
        if ($pass == 1) {
          return NULL;
        }
        continue;
      }

      /** @var \Drupal\Core\Database\Query\SelectInterface|null $word_query */
      $word_query = NULL;
      foreach ($fulltext_fields as $field) {
        if (!isset($fields[$field]) || !Utility::isTextType($fields[$field]['type'])) {
          continue;
        }
        $field_query = $this->database->select($fields[$field]['table'], 't');
        $field_query->fields('t', array('word', 'item_id'))
          ->condition('item_id', $all_results, 'IN');
        if ($pass == 1) {
          $field_query->condition('word', $incomplete_like, 'LIKE')
            ->condition('word', $keys, 'NOT IN');
        }
        if (!isset($word_query)) {
          $word_query = $field_query;
        }
        else {
          $word_query->union($field_query);
        }
      }
      if (!$word_query) {
        return array();
      }
      $db_query = $this->database->select($word_query, 't');
      $db_query->addExpression('COUNT(DISTINCT item_id)', 'results');
      $db_query->fields('t', array('word'))
        ->groupBy('word')
        ->having('results <= :max', array(':max' => $max_occurrences))
        ->orderBy('results', 'DESC')
        ->range(0, $limit);
      $incomp_len = strlen($incomplete_key);
      foreach ($db_query->execute() as $row) {
        $suffix = ($pass == 1) ? substr($row->word, $incomp_len) : ' ' . $row->word;
        $suggestions[] = array(
          'suggestion_suffix' => $suffix,
          'results' => $row->results,
        );
      }
    }

    return $suggestions;
  }

  /**
   * Retrieves the internal field information.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index whose fields should be retrieved.
   *
   * @return array[]
   *   An array of arrays. The outer array is keyed by field name. Each value
   *   is an associative array with information on the field.
   */
  protected function getFieldInfo(IndexInterface $index) {
    $db_info = $this->getIndexDbInfo($index);
    return $db_info['field_tables'];
  }

  /**
   * Retrieves the database info for the given index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index.
   *
   * @return array
   *   The index data from the key-value store.
   */
  protected function getIndexDbInfo(IndexInterface $index) {
    $db_info = $this->getKeyValueStore()->get($index->id(), array());
    if ($db_info && $db_info['server'] != $this->server->id()) {
      return array();
    }
    return $db_info;
  }

  /**
   * Implements the magic __sleep() method.
   *
   * Prevents the database connection and logger from being serialized.
   */
  public function __sleep() {
    $properties = array_flip(parent::__sleep());
    unset($properties['database']);
    unset($properties['logger']);
    return array_keys($properties);
  }

  /**
   * Implements the magic __wakeup() method.
   *
   * Reloads the database connection and logger.
   */
  public function __wakeup() {
    parent::__wakeup();

    if (isset($this->configuration['database'])) {
      list($key, $target) = explode(':', $this->configuration['database'], 2);
      $this->database = CoreDatabase::getConnection($target, $key);
    }
    $this->logger = \Drupal::service('logger.factory')->get('search_api_db');
  }

}

<?php

namespace Drupal\search_api;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceInterface;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\Core\TypedData\ListInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Item\Field;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Item\Item;
use Drupal\search_api\Query\Query;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSet;
use Symfony\Component\DependencyInjection\Container;

/**
 * Contains utility methods for the Search API.
 *
 * @todo Maybe move some of these methods to other classes (and/or split this
 *   class into several utility classes).
 */
class Utility {

  /**
   * Static cache for the field type mapping.
   *
   * @var array
   *
   * @see getFieldTypeMapping()
   */
  protected static $fieldTypeMapping = array();

  /**
   * Static cache for the fallback data type mapping per index.
   *
   * @var array
   *
   * @see getDataTypeFallbackMapping()
   */
  protected static $dataTypeFallbackMapping = array();

  /**
   * Determines whether fields of the given type contain fulltext data.
   *
   * @param string $type
   *   The type to check.
   * @param string[] $text_types
   *   (optional) An array of types to be considered as text.
   *
   * @return bool
   *   TRUE if $type is one of the specified types, FALSE otherwise.
   */
  // @todo Currently, this is useless, but later we could also check
  //   automatically for custom types that have one of the passed types as their
  //   fallback.
  public static function isTextType($type, array $text_types = array('text')) {
    return in_array($type, $text_types);
  }

  /**
   * Retrieves the mapping for known data types to Search API's internal types.
   *
   * @return string[]
   *   An array mapping all known (and supported) Drupal data types to their
   *   corresponding Search API data types. Empty values mean that fields of
   *   that type should be ignored by the Search API.
   *
   * @see hook_search_api_field_type_mapping_alter()
   */
  public static function getFieldTypeMapping() {
    // Check the static cache first.
    if (empty(static::$fieldTypeMapping)) {
      // It's easier to write and understand this array in the form of
      // $search_api_field_type => array($data_types) and flip it below.
      $default_mapping = array(
        'text' => array(
          'field_item:string_long.string',
          'field_item:text_long.string',
          'field_item:text_with_summary.string',
          'text',
        ),
        'string' => array(
          'string',
          'email',
          'uri',
          'filter_format',
          'duration_iso8601',
          'field_item:path',
        ),
        'integer' => array(
          'integer',
          'timespan',
        ),
        'decimal' => array(
          'decimal',
          'float',
        ),
        'date' => array(
          'datetime_iso8601',
          'timestamp',
        ),
        'boolean' => array(
          'boolean',
        ),
        // Types we know about but want/have to ignore.
        NULL => array(
          'language',
        ),
      );

      foreach ($default_mapping as $search_api_type => $data_types) {
        foreach ($data_types as $data_type) {
          $mapping[$data_type] = $search_api_type;
        }
      }

      // Allow other modules to intercept and define what default type they want
      // to use for their data type.
      \Drupal::moduleHandler()->alter('search_api_field_type_mapping', $mapping);

      static::$fieldTypeMapping = $mapping;
    }

    return static::$fieldTypeMapping;
  }

  /**
   * Retrieves the necessary type fallbacks for an index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index for which to return the type fallbacks.
   *
   * @return string[]
   *   An array containing the IDs of all custom data types that are not
   *   supported by the index's current server, mapped to their fallback types.
   */
  public static function getDataTypeFallbackMapping(IndexInterface $index) {
    // Check the static cache first.
    $index_id = $index->id();
    if (empty(static::$dataTypeFallbackMapping[$index_id])) {
      $server = NULL;
      try {
        $server = $index->getServerInstance();
      }
      catch (SearchApiException $e) {
        // If the server isn't available, just ignore it here and return all
        // custom types.
      }
      static::$dataTypeFallbackMapping[$index_id] = array();
      /** @var \Drupal\search_api\DataType\DataTypeInterface $data_type */
      foreach (\Drupal::service('plugin.manager.search_api.data_type')->getInstances() as $type_id => $data_type) {
        // We know for sure that we do not need to fall back for the default
        // data types as they are always present and are required to be
        // supported by all backends.
        if (!$data_type->isDefault() && (!$server || !$server->supportsDataType($type_id))) {
          static::$dataTypeFallbackMapping[$index_id][$type_id] = $data_type->getFallbackType();
        }
      }
    }

    return static::$dataTypeFallbackMapping[$index_id];
  }

  /**
   * Extracts specific field values from a complex data object.
   *
   * @param \Drupal\Core\TypedData\ComplexDataInterface $item
   *   The item from which fields should be extracted.
   * @param \Drupal\search_api\Item\FieldInterface[] $fields
   *   The field objects into which data should be extracted, keyed by their
   *   property paths on $item.
   */
  public static function extractFields(ComplexDataInterface $item, array $fields) {
    // Figure out which fields are directly on the item and which need to be
    // extracted from nested items.
    $direct_fields = array();
    $nested_fields = array();
    foreach (array_keys($fields) as $key) {
      if (strpos($key, ':') !== FALSE) {
        list($direct, $nested) = explode(':', $key, 2);
        $nested_fields[$direct][$nested] = $fields[$key];
      }
      else {
        $direct_fields[] = $key;
      }
    }
    // Extract the direct fields.
    foreach ($direct_fields as $key) {
      try {
        self::extractField($item->get($key), $fields[$key]);
      }
      catch (\InvalidArgumentException $e) {
        // This can happen with properties added by processors.
        // @todo Find a cleaner solution for this.
      }
    }
    // Recurse for all nested fields.
    foreach ($nested_fields as $direct => $fields_nested) {
      try {
        $item_nested = $item->get($direct);
        if ($item_nested instanceof DataReferenceInterface) {
          $item_nested = $item_nested->getTarget();
        }
        if ($item_nested instanceof EntityInterface) {
          $item_nested = $item_nested->getTypedData();
        }
        if ($item_nested instanceof ComplexDataInterface && !$item_nested->isEmpty()) {
          self::extractFields($item_nested, $fields_nested);
        }
        elseif ($item_nested instanceof ListInterface && !$item_nested->isEmpty()) {
          foreach ($item_nested as $list_item) {
            self::extractFields($list_item, $fields_nested);
          }
        }
      }
      catch (\InvalidArgumentException $e) {
        // This can happen with properties added by processors.
        // @todo Find a cleaner solution for this.
      }
    }
  }

  /**
   * Extracts value and original type from a single piece of data.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $data
   *   The piece of data from which to extract information.
   * @param \Drupal\search_api\Item\FieldInterface $field
   *   The field into which to put the extracted data.
   */
  public static function extractField(TypedDataInterface $data, FieldInterface $field) {
    $values = static::extractFieldValues($data);

    // If the data type of the field is a custom one, then the value can be
    // altered by the data type plugin.
    $data_type_manager = \Drupal::service('plugin.manager.search_api.data_type');
    /** @var \Drupal\search_api\DataType\DataTypeInterface $data_type_plugin */
    $data_type_plugin = NULL;
    if ($data_type_manager->hasDefinition($field->getType())) {
      $data_type_plugin = $data_type_manager->createInstance($field->getType());
    }

    foreach ($values as $i => $value) {
      if ($data_type_plugin) {
        $value = $data_type_plugin->getValue($value);
      }
      $field->addValue($value);
    }
    $field->setOriginalType($data->getDataDefinition()->getDataType());
  }

  /**
   * Extracts field values from a typed data object.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $data
   *   The typed data object.
   *
   * @return array
   *   An array of values.
   */
  public static function extractFieldValues(TypedDataInterface $data) {
    if ($data->getDataDefinition()->isList()) {
      $values = array();
      foreach ($data as $piece) {
        $values[] = self::extractFieldValues($piece);
      }
      return $values ? call_user_func_array('array_merge', $values) : array();
    }

    $value = $data->getValue();
    $definition = $data->getDataDefinition();
    if ($definition instanceof ComplexDataDefinitionInterface) {
      $property = $definition->getMainPropertyName();
      if (isset($value[$property])) {
        return array($value[$property]);
      }
    }
    elseif (is_array($value)) {
      return array_values($value);
    }
    return array($value);
  }

  /**
   * Retrieves a nested property from a list of properties.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface[] $properties
   *   The base properties, keyed by property name.
   * @param string $property_path
   *   The property path of the property to retrieve.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface|null
   *   The requested property, or NULL if it couldn't be found.
   */
  public static function retrieveNestedProperty(array $properties, $property_path) {
    list($key, $nested_path) = static::splitPropertyPath($property_path, FALSE);
    if (!isset($properties[$key])) {
      return NULL;
    }

    if (!isset($nested_path)) {
      return $properties[$key];
    }

    $property = static::getInnerProperty($properties[$key]);
    if (!$property instanceof ComplexDataDefinitionInterface) {
      return NULL;
    }

    return static::retrieveNestedProperty($property->getPropertyDefinitions(), $nested_path);
  }

  /**
   * Retrieves the inner property definition of a compound property definition.
   *
   * This will retrieve the list item type from a list data definition or the
   * definition of the referenced data from a reference data definition.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $property
   *   The original property definition.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   *   The inner property definition.
   */
  public static function getInnerProperty(DataDefinitionInterface $property) {
    while ($property instanceof ListDataDefinitionInterface) {
      $property = $property->getItemDefinition();
    }
    while ($property instanceof DataReferenceDefinitionInterface) {
      $property = $property->getTargetDefinition();
    }
    return $property;
  }

  /**
   * Splits a property path into two parts along a path separator (:).
   *
   * The path is split into one part with a single property name, and one part
   * with the complete rest of the property path (which might be empty).
   * Depending on $separate_last the returned single property key will be the
   * first (FALSE) or last (TRUE) property of the path.
   *
   * @param string $property_path
   *   The property path to split.
   * @param bool $separate_last
   *   (optional) If FALSE, separate the first property of the path. By default,
   *   the last property is separated from the rest.
   * @param string $separator
   *   (optional) The separator to use.
   *
   * @return string[]
   *   An array with indexes 0 and 1, 0 containing the first part of the
   *   property path and 1 the second. If $separate_last is FALSE, index 0 will
   *   always contain a single property name (without any colons) and index 1
   *   might be NULL. If $separate_last is TRUE it's the exact other way round.
   */
  public static function splitPropertyPath($property_path, $separate_last = TRUE, $separator = ':') {
    $function = $separate_last ? 'strrpos' : 'strpos';
    $pos = $function($property_path, $separator);
    if ($pos !== FALSE) {
      return array(
        substr($property_path, 0, $pos),
        substr($property_path, $pos + 1),
      );
    }

    return $separate_last ? array(NULL, $property_path) : array($property_path, NULL);
  }

  /**
   * Determines whether a field ID is reserved for special use.
   *
   * This is the case for the "magic" pseudo-fields documented in
   * \Drupal\search_api\Query\QueryInterface for use in queries, like
   * "search_api_id".
   *
   * @param string $field_id
   *   The field ID.
   *
   * @return bool
   *   TRUE if the field ID is reserved, FALSE if it can be used normally.
   */
  public static function isFieldIdReserved($field_id) {
    $reserved_ids = array_flip(array(
      'search_api_id',
      'search_api_datasource',
      'search_api_relevance',
    ));
    return isset($reserved_ids[$field_id]);
  }

  /**
   * Processes all pending index tasks inside a batch run.
   *
   * @param array $context
   *   The current batch context.
   * @param \Drupal\Core\Config\ConfigImporter $config_importer
   *   The config importer.
   */
  public static function processIndexTasks(array &$context, ConfigImporter $config_importer) {
    $index_task_manager = \Drupal::getContainer()->get('search_api.index_task_manager');

    if (!isset($context['sandbox']['indexes'])) {
      $context['sandbox']['indexes'] = array();

      $indexes = \Drupal::entityTypeManager()
        ->getStorage('search_api_index')
        ->loadByProperties(array(
          'status' => TRUE,
        ));
      $deleted = $config_importer->getUnprocessedConfiguration('delete');

      /** @var \Drupal\search_api\IndexInterface $index */
      foreach ($indexes as $index_id => $index) {
        if (!$index_task_manager->isTrackingComplete($index) && !in_array($index->getConfigDependencyName(), $deleted)) {
          $context['sandbox']['indexes'][] = $index_id;
        }
      }
      $context['sandbox']['total'] = count($context['sandbox']['indexes']);
      if (!$context['sandbox']['total']) {
        $context['finished'] = 1;
        return;
      }
    }

    $index_id = array_shift($context['sandbox']['indexes']);
    $index = Index::load($index_id);
    $added = $index_task_manager->addItemsOnce($index);
    if ($added !== NULL) {
      array_unshift($context['sandbox']['indexes'], $index_id);
    }

    if (empty($context['sandbox']['indexes'])) {
      $context['finished'] = 1;
    }
    else {
      $finished = $context['sandbox']['total'] - count($context['sandbox']['indexes']);
      $context['finished'] = $finished / $context['sandbox']['total'];
      $args = array(
        '%index' => $index->label(),
        '@num' => $finished + 1,
        '@total' => $context['sandbox']['total'],
      );
      $context['message'] = \Drupal::translation()->translate('Tracking items for search index %index (@num of @total)', $args);
    }
  }

  /**
   * Creates a new search query object.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index on which to search.
   * @param array $options
   *   (optional) The options to set for the query. See
   *   \Drupal\search_api\Query\QueryInterface::setOption() for a list of
   *   options that are recognized by default.
   *
   * @return \Drupal\search_api\Query\QueryInterface
   *   A search query object to use.
   *
   * @see \Drupal\search_api\Query\QueryInterface::create()
   */
  public static function createQuery(IndexInterface $index, array $options = array()) {
    $search_results_cache = \Drupal::service('search_api.results_static_cache');
    return Query::create($index, $search_results_cache, $options);
  }

  /**
   * Creates a new search result set.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The executed search query.
   *
   * @return \Drupal\search_api\Query\ResultSetInterface
   *   A search result set for the given query.
   */
  public static function createSearchResultSet(QueryInterface $query) {
    return new ResultSet($query);
  }

  /**
   * Creates a search item object.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The item's search index.
   * @param string $id
   *   The item's (combined) ID.
   * @param \Drupal\search_api\Datasource\DatasourceInterface|null $datasource
   *   (optional) The datasource of the item. If not set, it will be determined
   *   from the ID and loaded from the index if needed.
   *
   * @return \Drupal\search_api\Item\ItemInterface
   *   A search item with the given values.
   */
  public static function createItem(IndexInterface $index, $id, DatasourceInterface $datasource = NULL) {
    return new Item($index, $id, $datasource);
  }

  /**
   * Creates a search item object by wrapping an existing complex data object.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The item's search index.
   * @param \Drupal\Core\TypedData\ComplexDataInterface $original_object
   *   The original object to wrap.
   * @param string $id
   *   (optional) The item's (combined) ID. If not set, it will be determined
   *   with the \Drupal\search_api\Datasource\DatasourceInterface::getItemId()
   *   method of $datasource. In this case, $datasource must not be NULL.
   * @param \Drupal\search_api\Datasource\DatasourceInterface|null $datasource
   *   (optional) The datasource of the item. If not set, it will be determined
   *   from the ID and loaded from the index if needed.
   *
   * @return \Drupal\search_api\Item\ItemInterface
   *   A search item with the given values.
   *
   * @throws \InvalidArgumentException
   *   Thrown if both $datasource and $id are NULL.
   */
  public static function createItemFromObject(IndexInterface $index, ComplexDataInterface $original_object, $id = NULL, DatasourceInterface $datasource = NULL) {
    if (!isset($id)) {
      if (!isset($datasource)) {
        throw new \InvalidArgumentException('Need either an item ID or the datasource to create a search item from an object.');
      }
      $id = self::createCombinedId($datasource->getPluginId(), $datasource->getItemId($original_object));
    }
    $item = static::createItem($index, $id, $datasource);
    $item->setOriginalObject($original_object);
    return $item;
  }

  /**
   * Creates a new field object wrapping a field of the given index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index to which this field should be attached.
   * @param string $field_identifier
   *   The field identifier.
   * @param array $field_info
   *   (optional) An array with further configuration for the field.
   *
   * @return \Drupal\search_api\Item\FieldInterface
   *   A new field object.
   */
  public static function createField(IndexInterface $index, $field_identifier, $field_info = array()) {
    $field = new Field($index, $field_identifier);

    foreach ($field_info as $key => $value) {
      $method = 'set' . Container::camelize($key);
      if (method_exists($field, $method)) {
        $field->$method($value);
      }
    }

    return $field;
  }

  /**
   * Creates a new field on an index based on a property.
   *
   * Will find and set a new unique field identifier for the field on the index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $property
   *   The data definition of the property.
   * @param string|null $datasource_id
   *   The ID of the index's datasource this property belongs to, or NULL if it
   *   is a datasource-independent property.
   * @param string $property_path
   *   The property's property path within the property structure of the
   *   datasource.
   * @param string|null $field_id
   *   (optional) The identifier to use for the field. If not set, a new unique
   *   field identifier on the index will be chosen automatically.
   * @param string|null $type
   *   (optional) The type to set for the field, or NULL to determine a default
   *   type automatically.
   *
   * @return \Drupal\search_api\Item\FieldInterface
   *   A new field object for the index, based on the given property.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if no type was given and no default could be determined.
   */
  public static function createFieldFromProperty(IndexInterface $index, DataDefinitionInterface $property, $datasource_id, $property_path, $field_id = NULL, $type = NULL) {
    if (!isset($field_id)) {
      $field_id = static::getNewFieldId($index, $property_path);
    }

    if (!isset($type)) {
      $type_mapping = static::getFieldTypeMapping();
      $property_type = $property->getDataType();
      if (isset($type_mapping[$property_type])) {
        $type = $type_mapping[$property_type];
      }
      else {
        $args['%property'] = $property->getLabel();
        $args['%property_path'] = $property_path;
        $args['%type'] = $property_type;
        $message = new FormattableMarkup('No default data type mapping could be found for property %property (%property_path) of type %type.', $args);
        throw new SearchApiException($message);
      }
    }

    $field_info = array(
      'label' => $property->getLabel(),
      'datasource_id' => $datasource_id,
      'property_path' => $property_path,
      'type' => $type,
    );
    return self::createField($index, $field_id, $field_info);
  }

  /**
   * Finds a new unique field identifier on the given index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search index.
   * @param string $property_path
   *   The property path on which the field identifier should be based. Only the
   *   last component of the property path will be considered.
   *
   * @return string
   *   A new unique field identifier on the given index.
   */
  public static function getNewFieldId(IndexInterface $index, $property_path) {
    list(, $suggested_id) = static::splitPropertyPath($property_path);

    $field_id = $suggested_id;
    $i = 0;
    while ($index->getField($field_id)) {
      $field_id = $suggested_id . '_' . ++$i;
    }

    return $field_id;
  }

  /**
   * Creates a single token for the "tokenized_text" type.
   *
   * @param string $value
   *   The word or other token value.
   * @param float $score
   *   (optional) The token's score.
   *
   * @return array
   *   An array with appropriate "value" and "score" keys set.
   */
  public static function createTextToken($value, $score = 1.0) {
    return array(
      'value' => $value,
      'score' => (float) $score,
    );
  }

  /**
   * Returns a deep copy of the input array.
   *
   * The behavior of PHP regarding arrays with references pointing to it is
   * rather weird. Therefore, this method should be used when making a copy of
   * such an array, or of an array containing references.
   *
   * This method will also omit empty array elements (i.e., elements that
   * evaluate to FALSE according to PHP's native rules).
   *
   * @param array $array
   *   The array to copy.
   *
   * @return array
   *   A deep copy of the array.
   */
  public static function deepCopy(array $array) {
    $copy = array();
    foreach ($array as $k => $v) {
      if (is_array($v)) {
        if ($v = static::deepCopy($v)) {
          $copy[$k] = $v;
        }
      }
      elseif (is_object($v)) {
        $copy[$k] = clone $v;
      }
      elseif ($v) {
        $copy[$k] = $v;
      }
    }
    return $copy;
  }

  /**
   * Creates a combined ID from a raw ID and an optional datasource prefix.
   *
   * This can be used to created an internal item ID from a datasource ID and a
   * datasource-specific raw item ID, or a combined property path from a
   * datasource ID and a property path to identify properties index-wide.
   *
   * @param string|null $datasource_id
   *   The ID of the datasource to which the item belongs. Or NULL to return the
   *   raw ID unchanged (option included for compatibility purposes).
   * @param string $raw_id
   *   The datasource-specific raw item ID of the item (or property).
   *
   * @return string
   *   The combined ID, with the datasource prefix separated by
   *   \Drupal\search_api\IndexInterface::DATASOURCE_ID_SEPARATOR.
   */
  public static function createCombinedId($datasource_id, $raw_id) {
    if (!isset($datasource_id)) {
      return $raw_id;
    }
    return $datasource_id . IndexInterface::DATASOURCE_ID_SEPARATOR . $raw_id;
  }

  /**
   * Splits an internal ID into its two parts.
   *
   * Both internal item IDs and combined property paths are prefixed with the
   * corresponding datasource ID. This method will split these IDs up again into
   * their two parts.
   *
   * @param string $combined_id
   *   The internal ID, with an optional datasource prefix separated with
   *   \Drupal\search_api\IndexInterface::DATASOURCE_ID_SEPARATOR from the
   *   raw item ID or property path.
   *
   * @return array
   *   A numeric array, containing the datasource ID in element 0 and the raw
   *   item ID or property path in element 1. In the case of
   *   datasource-independent properties (i.e., when there is no prefix),
   *   element 0 will be NULL.
   */
  public static function splitCombinedId($combined_id) {
    return static::splitPropertyPath($combined_id, TRUE, IndexInterface::DATASOURCE_ID_SEPARATOR);
  }

}

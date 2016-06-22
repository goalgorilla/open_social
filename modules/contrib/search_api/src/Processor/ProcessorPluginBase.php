<?php

namespace Drupal\search_api\Processor;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\IndexPluginBase;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Utility;

/**
 * Defines a base class from which other processors may extend.
 *
 * Plugins extending this class need to define a plugin definition array through
 * annotation. These definition arrays may be altered through
 * hook_search_api_processor_info_alter(). The definition includes the following
 * keys:
 * - id: The unique, system-wide identifier of the processor.
 * - label: The human-readable name of the processor, translated.
 * - description: A human-readable description for the processor, translated.
 *
 * A complete plugin definition should be written as in this example:
 *
 * @code
 * @SearchApiProcessor(
 *   id = "my_processor",
 *   label = @Translation("My Processor"),
 *   description = @Translation("Does … something."),
 *   stages = {
 *     "preprocess_index" = 0,
 *     "preprocess_query" = 0,
 *     "postprocess_query" = 0
 *   }
 * )
 * @endcode
 *
 * @see \Drupal\search_api\Annotation\SearchApiProcessor
 * @see \Drupal\search_api\Processor\ProcessorPluginManager
 * @see \Drupal\search_api\Processor\ProcessorInterface
 * @see plugin_api
 */
abstract class ProcessorPluginBase extends IndexPluginBase implements ProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsStage($stage_identifier) {
    $plugin_definition = $this->getPluginDefinition();
    return isset($plugin_definition['stages'][$stage_identifier]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultWeight($stage) {
    $plugin_definition = $this->getPluginDefinition();
    return isset($plugin_definition['stages'][$stage]) ? (int) $plugin_definition['stages'][$stage] : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    return !empty($this->pluginDefinition['locked']);
  }

  /**
   * {@inheritdoc}
   */
  public function isHidden() {
    return !empty($this->pluginDefinition['hidden']);
  }

  /**
   * {@inheritdoc}
   */
  public function alterPropertyDefinitions(array &$properties, DatasourceInterface $datasource = NULL) {}

  /**
   * {@inheritdoc}
   */
  public function preIndexSave() {}

  /**
   * Ensures that a field with certain properties is indexed on the index.
   *
   * Can be used as a helper method in preIndexSave().
   *
   * @param string|null $datasource_id
   *   The ID of the field's datasource, or NULL for a datasource-independent
   *   field.
   * @param string $property_path
   *   The field's property path on the datasource.
   * @param string|null $type
   *   (optional) If set, the field should have this type.
   *
   * @return \Drupal\search_api\Item\FieldInterface
   *   A field on the index, possibly newly added, with the specified
   *   properties.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if there is no property with the specified path, or no type is
   *   given and no default could be determined for the property.
   */
  protected function ensureField($datasource_id, $property_path, $type = NULL) {
    $field = $this->findField($datasource_id, $property_path, $type);

    if (!$field) {
      $property = Utility::retrieveNestedProperty($this->index->getPropertyDefinitions($datasource_id), $property_path);
      if (!$property) {
        $args['%property'] = Utility::createCombinedId($datasource_id, $property_path);
        $args['%processor'] = $this->label();
        $message = new FormattableMarkup('Could not find property %property which is required by the %processor processor.', $args);
        throw new SearchApiException($message);
      }
      $field = Utility::createFieldFromProperty($this->index, $property, $datasource_id, $property_path, NULL, $type);
    }

    $field->setIndexedLocked();
    if (isset($type)) {
      $field->setTypeLocked();
    }
    $this->index->addField($field);
    return $field;
  }

  /**
   * Finds a certain field in the index.
   *
   * @param string|null $datasource_id
   *   The ID of the field's datasource, or NULL for a datasource-independent
   *   field.
   * @param string $property_path
   *   The field's property path on the datasource.
   * @param string|null $type
   *   (optional) If set, only return a field if it has this type.
   *
   * @return \Drupal\search_api\Item\FieldInterface|null
   *   A field on the index with the desired properties, or NULL if none could
   *   be found.
   */
  protected function findField($datasource_id, $property_path, $type = NULL) {
    foreach ($this->index->getFieldsByDatasource($datasource_id) as $field) {
      if ($field->getPropertyPath() == $property_path) {
        if (!isset($type) || $field->getType() == $type) {
          return $field;
        }
      }
    }
    return NULL;
  }

  /**
   * Filters the given fields for those with the specified property path.
   *
   * Array keys will be preserved.
   *
   * @param \Drupal\search_api\Item\FieldInterface[] $fields
   *   The fields to filter.
   * @param string $property_path
   *   The searched property path on the item.
   *
   * @return \Drupal\search_api\Item\FieldInterface[]
   *   All fields with the given property path.
   */
  protected function filterForPropertyPath(array $fields, $property_path) {
    $found_fields = array();
    foreach ($fields as $field_id => $field) {
      if ($field->getPropertyPath() == $property_path) {
        $found_fields[$field_id] = $field;
      }
    }
    return $found_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array &$items) {}

  /**
   * {@inheritdoc}
   */
  public function preprocessSearchQuery(QueryInterface $query) {}

  /**
   * {@inheritdoc}
   */
  public function postprocessSearchResults(ResultSetInterface $results) {}

  /**
   * {@inheritdoc}
   */
  public function requiresReindexing(array $old_settings = NULL, array $new_settings = NULL) {
    // Only require re-indexing for processors that actually run during the
    // indexing process.
    return $this->supportsStage(ProcessorInterface::STAGE_PREPROCESS_INDEX);
  }

}

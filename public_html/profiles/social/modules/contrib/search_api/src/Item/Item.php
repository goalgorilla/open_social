<?php

namespace Drupal\search_api\Item;

use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Utility;

/**
 * Provides a default implementation for a search item.
 */
class Item implements \IteratorAggregate, ItemInterface {

  /**
   * The search index with which this item is associated.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * The complex data item this Search API item is based on.
   *
   * @var \Drupal\Core\TypedData\ComplexDataInterface
   */
  protected $originalObject;

  /**
   * The ID of this item's datasource.
   *
   * @var string
   */
  protected $datasourceId;

  /**
   * The datasource of this item.
   *
   * @var \Drupal\search_api\Datasource\DatasourceInterface
   */
  protected $datasource;

  /**
   * The extracted fields of this item.
   *
   * @var \Drupal\search_api\Item\FieldInterface[]
   */
  protected $fields = array();

  /**
   * Whether the fields were already extracted for this item.
   *
   * @var bool
   */
  protected $fieldsExtracted = FALSE;

  /**
   * The HTML text with highlighted text-parts that match the query.
   *
   * @var string
   */
  protected $excerpt;

  /**
   * The score this item had as a result in a corresponding search query.
   *
   * @var float
   */
  protected $score = 1.0;

  /**
   * The boost of this item at indexing time.
   *
   * @var float
   */
  protected $boost = 1.0;

  /**
   * Extra data set on this item.
   *
   * @var array
   */
  protected $extraData = array();

  /**
   * Constructs an Item object.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The item's search index.
   * @param string $id
   *   The ID of this item.
   * @param \Drupal\search_api\Datasource\DatasourceInterface|null $datasource
   *   (optional) The datasource of this item. If not set, it will be determined
   *   from the ID and loaded from the index.
   */
  public function __construct(IndexInterface $index, $id, DatasourceInterface $datasource = NULL) {
    $this->index = $index;
    $this->id = $id;
    if ($datasource) {
      $this->datasource = $datasource;
      $this->datasourceId = $datasource->getPluginId();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDatasourceId() {
    if (!isset($this->datasourceId)) {
      list($this->datasourceId) = Utility::splitCombinedId($this->id);
    }
    return $this->datasourceId;
  }

  /**
   * {@inheritdoc}
   */
  public function getDatasource() {
    if (!isset($this->datasource)) {
      $this->datasource = $this->index->getDatasource($this->getDatasourceId());
    }
    return $this->datasource;
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
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalObject($load = TRUE) {
    if (!isset($this->originalObject) && $load) {
      $this->originalObject = $this->index->loadItem($this->id);
      if (!$this->originalObject) {
        throw new SearchApiException('Failed to load original object ' . $this->id);
      }
    }
    return $this->originalObject;
  }

  /**
   * {@inheritdoc}
   */
  public function setOriginalObject(ComplexDataInterface $original_object) {
    $this->originalObject = $original_object;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getField($field_id, $extract = TRUE) {
    if (isset($this->fields[$field_id])) {
      return $this->fields[$field_id];
    }
    $fields = $this->getFields($extract);
    return isset($fields[$field_id]) ? $fields[$field_id] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFields($extract = TRUE) {
    if ($extract && !$this->fieldsExtracted) {
      $data_type_fallback_mapping = Utility::getDataTypeFallbackMapping($this->index);
      foreach (array(NULL, $this->getDatasourceId()) as $datasource_id) {
        $fields_by_property_path = array();
        foreach ($this->index->getFieldsByDatasource($datasource_id) as $field_id => $field) {
          // Don't overwrite fields that were previously set.
          if (empty($this->fields[$field_id])) {
            $this->fields[$field_id] = clone $field;

            $field_data_type = $this->fields[$field_id]->getType();
            // If the field data type is in the fallback mapping list, then use
            // the fallback type as field type.
            if (isset($data_type_fallback_mapping[$field_data_type])) {
              $this->fields[$field_id]->setType($data_type_fallback_mapping[$field_data_type]);
            }

            $fields_by_property_path[$field->getPropertyPath()] = $this->fields[$field_id];
          }
        }
        if ($datasource_id && $fields_by_property_path) {
          try {
            Utility::extractFields($this->getOriginalObject(), $fields_by_property_path);
          }
          catch (SearchApiException $e) {
            // If we couldn't load the object, just log an error and fail
            // silently to set the values.
            watchdog_exception('search_api', $e);
          }
        }
      }
      $this->fieldsExtracted = TRUE;
    }
    return $this->fields;
  }

  /**
   * {@inheritdoc}
   */
  public function setField($field_id, FieldInterface $field = NULL) {
    if ($field) {
      if ($field->getFieldIdentifier() !== $field_id) {
        throw new \InvalidArgumentException('The field identifier passed must be consistent with the identifier set on the field object.');
      }
      // Make sure that the field has the same index object set as we. This
      // might otherwise cause impossibly hard-to-detect bugs.
      $field->setIndex($this->index);
      $this->fields[$field_id] = $field;
    }
    else {
      unset($this->fields[$field_id]);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setFields(array $fields) {
    // Make sure that all fields have the same index object set as we. This
    // might otherwise cause impossibly hard-to-detect bugs.
    /** @var \Drupal\search_api\Item\FieldInterface $field */
    foreach ($fields as $field) {
      $field->setIndex($this->index);
    }
    $this->fields = $fields;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldsExtracted() {
    return $this->fieldsExtracted;
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldsExtracted($fields_extracted) {
    $this->fieldsExtracted = $fields_extracted;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getScore() {
    return $this->score;
  }

  /**
   * {@inheritdoc}
   */
  public function setScore($score) {
    $this->score = $score;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBoost() {
    return $this->boost;
  }

  /**
   * {@inheritdoc}
   */
  public function setBoost($boost) {
    $this->boost = $boost;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getExcerpt() {
    return $this->excerpt;
  }

  /**
   * {@inheritdoc}
   */
  public function setExcerpt($excerpt) {
    $this->excerpt = $excerpt;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasExtraData($key) {
    return array_key_exists($key, $this->extraData);
  }

  /**
   * {@inheritdoc}
   */
  public function getExtraData($key, $default = NULL) {
    return array_key_exists($key, $this->extraData) ? $this->extraData[$key] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllExtraData() {
    return $this->extraData;
  }

  /**
   * {@inheritdoc}
   */
  public function setExtraData($key, $data = NULL) {
    if (isset($data)) {
      $this->extraData[$key] = $data;
    }
    else {
      unset($this->extraData[$key]);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \ArrayIterator($this->getFields());
  }

  /**
   * Implements the magic __clone() method to implement a deep clone.
   */
  public function __clone() {
    // The fields definitely need to be cloned. For the extra data its hard (or,
    // rather, impossible) to tell, but we opt for cloning objects there, too,
    // to be on the (hopefully) safer side. (Ideas for later: introduce an
    // interface that tells us to not clone the data object; or check whether
    // its an entity; or introduce some other system to override this default.)
    foreach ($this->fields as $field_id => $field) {
      $this->fields[$field_id] = clone $field;
    }
    foreach ($this->extraData as $key => $data) {
      if (is_object($data)) {
        $this->extraData[$key] = clone $data;
      }
    }
  }

  /**
   * Implements the magic __toString() method to simplify debugging.
   */
  public function __toString() {
    $out = 'Item ' . $this->getId();
    if ($this->getScore() != 1) {
      $out .= "\nScore: " . $this->getScore();
    }
    if ($this->getBoost() != 1) {
      $out .= "\nBoost: " . $this->getBoost();
    }
    if ($this->getExcerpt()) {
      $excerpt = str_replace("\n", "\n  ", $this->getExcerpt());
      $out .= "\nExcerpt: $excerpt";
    }
    if ($this->getFields()) {
      $out .= "\nFields:";
      foreach ($this->getFields() as $field) {
        $field = str_replace("\n", "\n  ", "$field");
        $out .= "\n- " . $field;
      }
    }
    if ($this->getAllExtraData()) {
      $data = str_replace("\n", "\n  ", print_r($this->getAllExtraData(), TRUE));
      $out .= "\nExtra data: " . $data;
    }
    return $out;
  }

}

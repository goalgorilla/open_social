<?php

namespace Drupal\search_api\Item;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Utility;

/**
 * Represents a field on a search item that can be indexed.
 */
class Field implements \IteratorAggregate, FieldInterface {

  /**
   * The index this field is attached to.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * The ID of the index this field is attached to.
   *
   * This is only used to avoid serialization of the index in __sleep() and
   * __wakeup().
   *
   * @var string
   */
  protected $indexId;

  /**
   * The field's identifier.
   *
   * @var string
   */
  protected $fieldIdentifier;

  /**
   * The field's datasource's ID.
   *
   * @var string|null
   */
  protected $datasourceId;

  /**
   * The field's datasource.
   *
   * @var \Drupal\search_api\Datasource\DatasourceInterface|null
   */
  protected $datasource;

  /**
   * The property path on the search object.
   *
   * @var string
   */
  protected $propertyPath;

  /**
   * This field's data definition.
   *
   * @var \Drupal\Core\TypedData\DataDefinitionInterface
   */
  protected $dataDefinition;

  /**
   * The human-readable label for this field.
   *
   * @var string
   */
  protected $label;

  /**
   * The human-readable description for this field.
   *
   * FALSE if the field has no description.
   *
   * @var string|false
   */
  protected $description;

  /**
   * The human-readable label for this field's datasource.
   *
   * @var string
   */
  protected $labelPrefix;

  /**
   * The Search API data type of this field.
   *
   * @var string
   */
  protected $type;

  /**
   * The boost assigned to this field, if any.
   *
   * @var float
   */
  protected $boost;

  /**
   * Whether this field should be hidden from the user.
   *
   * @var bool
   */
  protected $hidden;

  /**
   * Whether this field should always be enabled/indexed.
   *
   * @var bool
   */
  protected $indexedLocked;

  /**
   * Whether this field type should be locked.
   *
   * @var bool
   */
  protected $typeLocked;

  /**
   * The field's values.
   *
   * @var array
   */
  protected $values = array();

  /**
   * The original data type of this field.
   *
   * @var string
   */
  protected $originalType;

  /**
   * Constructs a Field object.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The field's index.
   * @param string $field_identifier
   *   The field's identifier.
   */
  public function __construct(IndexInterface $index, $field_identifier) {
    $this->index = $index;
    $this->fieldIdentifier = $field_identifier;
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
  public function setIndex(IndexInterface $index) {
    if ($this->index->id() != $index->id()) {
      throw new \InvalidArgumentException('Attempted to change the index of a field object.');
    }
    $this->index = $index;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldIdentifier() {
    return $this->fieldIdentifier;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    $settings = array(
      'label' => $this->getLabel(),
      'datasource_id' => $this->getDatasourceId(),
      'property_path' => $this->getPropertyPath(),
      'type' => $this->getType(),
    );
    if ($this->getBoost() != 1.0) {
      $settings['boost'] = $this->getBoost();
    }
    if ($this->isIndexedLocked()) {
      $settings['indexed_locked'] = TRUE;
    }
    if ($this->isTypeLocked()) {
      $settings['type_locked'] = TRUE;
    }
    if ($this->isHidden()) {
      $settings['hidden'] = TRUE;
    }
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getDatasourceId() {
    return $this->datasourceId;
  }

  /**
   * {@inheritdoc}
   */
  public function getDatasource() {
    if (!isset($this->datasource) && isset($this->datasourceId)) {
      $this->datasource = $this->index->getDatasource($this->datasourceId);
    }
    return $this->datasource;
  }

  /**
   * {@inheritdoc}
   */
  public function setDatasourceId($datasource_id) {
    $this->datasourceId = $datasource_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyPath() {
    return $this->propertyPath;
  }

  /**
   * {@inheritdoc}
   */
  public function setPropertyPath($property_path) {
    $this->propertyPath = $property_path;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCombinedPropertyPath() {
    return Utility::createCombinedId($this->getDatasourceId(), $this->getPropertyPath());
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    if (!isset($this->description)) {
      try {
        $this->description = $this->getDataDefinition()->getDescription();
        $this->description = $this->description ?: FALSE;
      }
      catch (SearchApiException $e) {
        watchdog_exception('search_api', $e);
      }
    }
    return $this->description ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    // Set FALSE instead of NULL so caching will work properly.
    $this->description = $description ?: FALSE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrefixedLabel() {
    if (!isset($this->labelPrefix)) {
      $this->labelPrefix = '';
      if (isset($this->datasourceId)) {
        $this->labelPrefix = $this->datasourceId;
        try {
          $this->labelPrefix = $this->getDatasource()->label();
        }
        catch (SearchApiException $e) {
          watchdog_exception('search_api', $e);
        }
        $this->labelPrefix .= ' » ';
      }
    }
    return $this->labelPrefix . $this->getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function setLabelPrefix($label_prefix) {
    $this->labelPrefix = $label_prefix;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isHidden() {
    return (bool) $this->hidden;
  }

  /**
   * {@inheritdoc}
   */
  public function setHidden($hidden = TRUE) {
    $this->hidden = $hidden;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataDefinition() {
    if (!isset($this->dataDefinition)) {
      $definitions = $this->index->getPropertyDefinitions($this->getDatasourceId());
      $definition = $this->getNestedDefinition($definitions, explode(':', $this->getPropertyPath()));
      if (!$definition) {
        $args['%field'] = $this->getLabel();
        $args['%index'] = $this->getIndex()->label();
        throw new SearchApiException(new FormattableMarkup('Could not retrieve data definition for field %field on index %index.', $args));
      }
      $this->dataDefinition = $definition;
    }
    return $this->dataDefinition;
  }

  /**
   * Retrieves a nested property definition from an array of definitions.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface[] $properties
   *   The given array of base definitions.
   * @param string[] $keys
   *   An array of keys to apply to the definitions to arrive at the one that
   *   should be returned.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface|null
   *   The requested property definition, or NULL if it could not be found.
   */
  protected function getNestedDefinition(array $properties, array $keys) {
    $key = array_shift($keys);
    if (!isset($properties[$key])) {
      return NULL;
    }
    $property = Utility::getInnerProperty($properties[$key]);
    if (!$keys) {
      return $property;
    }
    if (!$property instanceof ComplexDataDefinitionInterface) {
      return NULL;
    }
    return $this->getNestedDefinition($property->getPropertyDefinitions(), $keys);
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    if ($type != $this->type && $this->isTypeLocked()) {
      $args['%field'] = $this->getLabel();
      $args['%index'] = $this->getIndex()->label();
      throw new SearchApiException(new FormattableMarkup('Trying to change the type of field %field on index %index, which is locked.', $args));
    }
    $this->type = $type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getValues() {
    return $this->values;
  }

  /**
   * {@inheritdoc}
   */
  public function setValues(array $values) {
    $this->values = array_values($values);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addValue($value) {
    $this->values[] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalType() {
    if (!isset($this->originalType)) {
      $this->originalType = 'string';
      try {
        $this->originalType = $this->getDataDefinition()->getDataType();
      }
      catch (SearchApiException $e) {
        watchdog_exception('search_api', $e);
      }
    }
    return $this->originalType;
  }

  /**
   * {@inheritdoc}
   */
  public function setOriginalType($original_type) {
    $this->originalType = $original_type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBoost() {
    return isset($this->boost) ? $this->boost : 1.0;
  }

  /**
   * {@inheritdoc}
   */
  public function setBoost($boost) {
    $this->boost = (float) $boost;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isIndexedLocked() {
    return (bool) $this->indexedLocked;
  }

  /**
   * {@inheritdoc}
   */
  public function setIndexedLocked($indexed_locked = TRUE) {
    $this->indexedLocked = $indexed_locked;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isTypeLocked() {
    return (bool) $this->typeLocked;
  }

  /**
   * {@inheritdoc}
   */
  public function setTypeLocked($type_locked = TRUE) {
    $this->typeLocked = $type_locked;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \ArrayIterator($this->values);
  }

  /**
   * Implements the magic __toString() method to simplify debugging.
   */
  public function __toString() {
    $label = $this->getLabel();
    $field_id = $this->getFieldIdentifier();
    $type = $this->getType();
    $out = "$label [$field_id]: indexed as type $type";
    if (Utility::isTextType($type)) {
      $out .= ' (boost ' . $this->getBoost() . ')';
    }
    if ($this->getValues()) {
      $out .= "\nValues:";
      foreach ($this->getValues() as $value) {
        $value = str_replace("\n", "\n  ", "$value");
        $out .= "\n- " . $value;
      }
    }
    return $out;
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    $this->indexId = $this->index->id();
    $properties = get_object_vars($this);
    // Don't serialize objects in properties or the field values.
    unset($properties['index'], $properties['datasource'], $properties['dataDefinition'], $properties['values']);
    return array_keys($properties);
  }

  /**
   * Implements the magic __wakeup() method to control object unserialization.
   */
  public function __wakeup() {
    if ($this->indexId) {
      $this->index = Index::load($this->indexId);
      unset($this->indexId);
    }
  }

}

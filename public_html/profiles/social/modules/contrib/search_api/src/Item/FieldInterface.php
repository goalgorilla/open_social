<?php

namespace Drupal\search_api\Item;

use Drupal\search_api\IndexInterface;

/**
 * Represents a field on a search item that can be indexed.
 *
 * Traversing the object retrieves all its values.
 */
interface FieldInterface extends \Traversable {

  /**
   * Returns the index of this field.
   *
   * @return \Drupal\search_api\IndexInterface
   *   The index to which this field belongs.
   */
  public function getIndex();

  /**
   * Returns the index of this field.
   *
   * This is useful when retrieving fields from cache, to have the index always
   * set to the same object that is returning them. The method shouldn't be used
   * in any other case.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index to which this field belongs.
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   *   Thrown if the ID of the given index is not the same as the ID of the
   *   index that was set up to now.
   */
  public function setIndex(IndexInterface $index);

  /**
   * Returns the field identifier of this field.
   *
   * @return string
   *   The identifier of this field.
   */
  public function getFieldIdentifier();

  /**
   * Retrieves all settings encapsulated in this field as an array.
   *
   * @return array
   *   An associative array of field settings.
   */
  public function getSettings();

  /**
   * Retrieves the ID of this field's datasource.
   *
   * @return string|null
   *   The plugin ID of this field's datasource, or NULL if the field is
   *   datasource-independent.
   */
  public function getDatasourceId();

  /**
   * Returns the datasource of this field.
   *
   * @return \Drupal\search_api\Datasource\DatasourceInterface|null
   *   The datasource to which this field belongs. NULL if the field is
   *   datasource-independent.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if the field's datasource couldn't be loaded.
   */
  public function getDatasource();

  /**
   * Sets the ID of this field's datasource.
   *
   * @param string|null $datasource_id
   *   The plugin ID of this field's datasource, or NULL if the field is
   *   datasource-independent.
   *
   * @return $this
   */
  public function setDatasourceId($datasource_id);

  /**
   * Retrieves this field's property path.
   *
   * @return string
   *   The property path.
   */
  public function getPropertyPath();

  /**
   * Retrieves this field's property path.
   *
   * @param string $property_path
   *   The property path.
   *
   * @return $this
   */
  public function setPropertyPath($property_path);

  /**
   * Retrieves the "combined" property path of the field.
   *
   * This consists of the datasource ID (if any) and the property path,
   * separated by the "datasource separator" (also used in item IDs). It can be
   * used to quickly get a unique identifier for a property on an index.
   *
   * @return string
   *   The "combined" property path of the field.
   *
   * @see \Drupal\search_api\Utility::createCombinedId()
   */
  public function getCombinedPropertyPath();

  /**
   * Retrieves this field's label.
   *
   * The field's label, contrary to the label returned by the field's data
   * definition, contains a human-readable representation of the full property
   * path. The datasource label is not included, though – use getPrefixedLabel()
   * for that.
   *
   * @return string
   *   A human-readable label representing this field's property path.
   */
  public function getLabel();

  /**
   * Sets this field's label.
   *
   * @param string $label
   *   A human-readable label representing this field's property path.
   *
   * @return $this
   */
  public function setLabel($label);

  /**
   * Retrieves this field's description.
   *
   * @return string|null
   *   A human-readable description for this field, or NULL if the field has no
   *   description.
   */
  public function getDescription();

  /**
   * Sets this field's description.
   *
   * @param string|null $description
   *   A human-readable description for this field, or NULL if the field has no
   *   description.
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Retrieves this field's label along with datasource prefix.
   *
   * Returns a value similar to getLabel(), but also contains the datasource
   * label, if applicable.
   *
   * @return string
   *   A human-readable label representing this field's property path and
   *   datasource.
   */
  public function getPrefixedLabel();

  /**
   * Sets this field's label prefix.
   *
   * @param string $label_prefix
   *   A human-readable label representing this field's datasource and ending in
   *   some kind of visual separator.
   *
   * @return $this
   */
  public function setLabelPrefix($label_prefix);

  /**
   * Determines whether this field should be hidden from the user.
   *
   * @return bool
   *   TRUE if this field should be hidden from the user.
   */
  public function isHidden();

  /**
   * Sets whether this field should be hidden from the user.
   *
   * @param bool $hidden
   *   (optional) TRUE if the field should be hidden, FALSE otherwise.
   *
   * @return $this
   */
  public function setHidden($hidden = TRUE);

  /**
   * Retrieves this field's data definition.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   *   The data definition object for this field.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if the field's data definition is unknown.
   */
  public function getDataDefinition();

  /**
   * Retrieves the Search API data type of this field.
   *
   * @return string
   *   The data type of the field.
   */
  public function getType();

  /**
   * Sets the Search API data type of this field.
   *
   * @param string $type
   *   The data type of the field.
   *
   * @return $this
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if the type of this field is locked.
   */
  public function setType($type);

  /**
   * Retrieves the value of this field.
   *
   * @return array
   *   A numeric array of zero or more values for this field, with indices
   *   starting with 0.
   */
  public function getValues();

  /**
   * Sets the values of this field.
   *
   * @param array $values
   *   The values of the field.
   *
   * @return $this
   */
  public function setValues(array $values);

  /**
   * Adds a value to this field.
   *
   * @param mixed $value
   *   A value to add to this field.
   *
   * @return $this
   */
  public function addValue($value);

  /**
   * Retrieves the original data type of this field.
   *
   * This is the Drupal data type of the original property definition, which
   * might not be a valid Search API data type. Instead it has to be a type that
   * is recognized by
   * \Drupal\Core\TypedData\TypedDataManager::createDataDefinition().
   *
   * @return string
   *   The original data type.
   */
  public function getOriginalType();

  /**
   * Sets the original data type of this field.
   *
   * @param string $original_type
   *   The field's original data type.
   *
   * @return $this
   */
  public function setOriginalType($original_type);

  /**
   * Retrieves the field's boost value.
   *
   * @return float
   *   The boost set for this field. Defaults to 1.0 and is mostly only relevant
   *   for fulltext fields.
   */
  public function getBoost();

  /**
   * Sets the field's boost value.
   *
   * @param float $boost
   *   The new boost value.
   *
   * @return $this
   */
  public function setBoost($boost);

  /**
   * Determines whether this field should always be enabled/indexed.
   *
   * @return bool
   *   TRUE if this field should be locked as enabled/indexed.
   */
  public function isIndexedLocked();

  /**
   * Sets whether this field should be locked.
   *
   * @param bool $indexed_locked
   *   (optional) TRUE if the field should be locked, FALSE otherwise.
   *
   * @return $this
   */
  public function setIndexedLocked($indexed_locked = TRUE);

  /**
   * Determines whether the type of this field should be locked.
   *
   * @return bool
   *   TRUE if the type of this field should be locked.
   */
  public function isTypeLocked();

  /**
   * Sets whether the type of this field should be locked.
   *
   * @param bool $type_locked
   *   (optional) TRUE if the type of the field should be locked, FALSE
   *   otherwise.
   *
   * @return $this
   */
  public function setTypeLocked($type_locked = TRUE);

  /**
   * Retrieves the field's dependencies.
   *
   * @return string[][]
   *   The field's dependencies.
   */
  public function getDependencies();

  /**
   * Sets the field's dependencies.
   *
   * @param string[][] $dependencies
   *   The field's dependencies.
   *
   * @return $this
   */
  public function setDependencies(array $dependencies);

}

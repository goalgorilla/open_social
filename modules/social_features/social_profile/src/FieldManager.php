<?php

declare(strict_types=1);

namespace Drupal\social_profile;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Manages the visibility and editability of profile fields.
 *
 * Decorates the Drupal field manager service.
 */
class FieldManager implements EntityFieldManagerInterface {

  /**
   * The Drupal entity field manager.
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * FieldManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The Drupal entity field manager.
   */
  public function __construct(EntityFieldManagerInterface $entityFieldManager) {
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * Get the fields available on the profile that we manage visibility for.
   *
   * @param string $bundle
   *   Bundle to get fields for (defaults to 'profile').
   *
   * @return \Drupal\Core\Field\FieldConfigInterface[]
   *   A list of managed field configurations, keyed by field name.
   *
   * @see \Drupal\social_profile\FieldManager::isOptedOutOfFieldAccessManagement()
   */
  public function getManagedProfileFieldDefinitions(string $bundle = 'profile') : array {
    return array_filter(
      $this->getFieldDefinitions('profile', $bundle),
      // Indirection until https://github.com/phpstan/phpstan/issues/8528.
      fn (FieldDefinitionInterface $definition) => static::isManagedValueField($definition)
    );
  }

  /**
   * Whether the field has opted out of our access management.
   *
   * Modules can opt-out for field management by this module by setting the
   * `social_profile` third-party setting `managed_access` to FALSE for the
   * field definition. Fields without value will be managed.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition for which we are checking.
   *
   * @return bool
   *   Whether the field has opted out of access management by this module.
   */
  public static function isOptedOutOfFieldAccessManagement(FieldDefinitionInterface $field_definition) : bool {
    return !self::fieldIsConfigurable($field_definition) || !self::getFieldStorageDefinition($field_definition)->getThirdPartySetting('social_profile', 'managed_access', TRUE);
  }

  /**
   * Get the name of the field that this field contains visibility info for.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field that contains visibility data.
   *
   * @return string|null
   *   The name of the field or NULL if $field_definition is not a field that
   *   contains field visibility data.
   */
  public static function getManagedValueFieldName(FieldDefinitionInterface $field_definition) : ?string {
    // Visibility can only be managed for fieldable fields.
    if (!self::fieldIsConfigurable($field_definition)) {
      return NULL;
    }

    return self::getFieldStorageDefinition($field_definition)->getThirdPartySetting('social_profile', 'visibility_for');
  }

  /**
   * Get the name of the field that contains visibility data.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field to get the visibility field name for.
   *
   * @return string|null
   *   The name of the field containing visibility data or NULL if
   *   $field_definition is not a field that manages visibility for another
   *   field.
   */
  public static function getVisibilityFieldName(FieldDefinitionInterface $field_definition) : ?string {
    // Visibility can only be managed by fieldable fields.
    if (!self::fieldIsConfigurable($field_definition)) {
      return NULL;
    }

    return self::getFieldStorageDefinition($field_definition)->getThirdPartySetting('social_profile', 'visibility_stored_by');
  }

  /**
   * Whether the provided field manages visibility data for another field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field to check.
   *
   * @return bool
   *   Whether the provided field manages visibility data for another field.
   *
   * @phpstan-assert-if-true \Drupal\Core\Field\FieldConfigInterface $field_definition
   */
  public static function isVisibilityField(FieldDefinitionInterface $field_definition) : bool {
    return static::getManagedValueFieldName($field_definition) !== NULL;
  }

  /**
   * Whether the provided field has another field that manages visibility data.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field to check.
   *
   * @return bool
   *   Whether the provided field has another field that manages visibility
   *   data.
   *
   * @phpstan-assert-if-true \Drupal\Core\Field\FieldConfigInterface $field_definition
   */
  public static function isManagedValueField(FieldDefinitionInterface $field_definition) : bool {
    return !static::isOptedOutOfFieldAccessManagement($field_definition) && static::getVisibilityFieldName($field_definition) !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFieldDefinitions($entity_type_id) {
    return $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinitions($entity_type_id, $bundle) {
    return $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldStorageDefinitions($entity_type_id) {
    return $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldMap() {
    return $this->entityFieldManager->getFieldMap();
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldMap(array $field_map) {
    $this->entityFieldManager->setFieldMap($field_map);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldMapByFieldType($field_type) {
    return $this->entityFieldManager->getFieldMapByFieldType($field_type);
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedFieldDefinitions() : void {
    $this->entityFieldManager->clearCachedFieldDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function useCaches($use_caches = FALSE) : void {
    $this->entityFieldManager->useCaches($use_caches);
  }

  /**
   * {@inheritdoc}
   */
  public function getExtraFields($entity_type_id, $bundle) {
    return $this->entityFieldManager->getExtraFields($entity_type_id, $bundle);
  }

  /**
   * Type-checker satisfaction function.
   *
   * A \Drupal\field\FieldConfigInterface will always have a
   * \Drupal\field\FieldStorageConfigInterface instance as storage. This is
   * guarded by the implementation. However the interface of the original
   * interface is not adjusted so the type-checker gets confused.
   *
   * @param \Drupal\Core\Field\FieldConfigInterface $field_definition
   *   The field definition to call `getFieldStorageDefinition` on.
   *
   * @return \Drupal\field\FieldStorageConfigInterface
   *   The field storage config entity.
   *
   * @see \Drupal\field\Entity\FieldConfig::getFieldStorageDefinition()
   * @see https://www.drupal.org/project/drupal/issues/2399301
   * @see https://www.drupal.org/project/drupal/issues/2818877
   */
  private static function getFieldStorageDefinition(FieldConfigInterface $field_definition) : FieldStorageConfigInterface {
    /** @var \Drupal\field\FieldStorageConfigInterface $fsd */
    $fsd = $field_definition->getFieldStorageDefinition();
    return $fsd;
  }

  /**
   * Check that a field definition is configurable.
   *
   * It must be configurable so that we can add our own metadata.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition to check.
   *
   * @return bool
   *   Whether the class implements \Drupal\Core\Field\FieldConfigInterface.
   *
   * @phpstan-assert-if-true \Drupal\Core\Field\FieldConfigInterface $field_definition
   */
  private static function fieldIsConfigurable(FieldDefinitionInterface $field_definition) : bool {
    return $field_definition instanceof FieldConfigInterface;
  }

}

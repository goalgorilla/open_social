<?php

declare(strict_types=1);

namespace Drupal\social_profile;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\field\FieldConfigInterface;
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
   * Modules can opt-out for field management by this module by setting the
   * `social_profile` third-party setting `managed_access` to FALSE for the
   * field definition. An empty value is treated as TRUE.
   *
   * @param string $bundle
   *   Optional bundle to get fields for (defaults to 'profile').
   *
   * @return \Drupal\field\FieldConfigInterface[]
   *   A list of managed field configurations, keyed by field name.
   */
  public function getManagedProfileFieldDefinitions(string $bundle = 'profile') : array {
    return array_filter(
      $this->getFieldDefinitions('profile', $bundle),
      fn (FieldDefinitionInterface $fieldDefinition) => $fieldDefinition instanceof FieldConfigInterface && $fieldDefinition->getThirdPartySetting('social_profile', 'managed_access', TRUE)
    );
  }

  /**
   * Get the field that stores user visibility choices for a field.
   *
   * @param \Drupal\field\FieldConfigInterface $field
   *   The field for which to find the visibility field.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface|null
   *   The field storing field visibility choices or NULL if the provided field
   *   doesn't support changing visibility.
   */
  public function getVisibilityFieldFor(FieldConfigInterface $field) : ?FieldDefinitionInterface {
    $bundle = $field->getTargetBundle();
    if ($bundle === NULL) {
      return NULL;
    }

    $field_storage = $field->getFieldStorageDefinition();
    if (!$field_storage instanceof FieldStorageConfigInterface) {
      return NULL;
    }

    $visibility_storage_id = $field_storage->getThirdPartySetting('social_profile', 'visibility_stored_by');
    if ($visibility_storage_id === NULL) {
      return NULL;
    }

    $config_name_parts = explode(".", $visibility_storage_id);
    $field_name = end($config_name_parts);
    $bundle_fields = $this->getFieldDefinitions($field->getTargetEntityTypeId(), $bundle);

    return $bundle_fields[$field_name] ?? NULL;
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

}

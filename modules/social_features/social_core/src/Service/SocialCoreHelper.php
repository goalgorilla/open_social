<?php

namespace Drupal\social_core\Service;

use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Entity\EntityTypeListenerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldStorageDefinitionListenerInterface;

/**
 * Class SocialCoreHelper.
 *
 * @package Drupal\social_core\Service
 */
class SocialCoreHelper implements SocialCoreHelperInterface {

  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity last installed schema repository.
   *
   * @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface
   */
  protected $entityLastInstalledSchemaRepository;

  /**
   * The entity type listener.
   *
   * @var \Drupal\Core\Entity\EntityTypeListenerInterface
   */
  protected $entityTypeListener;

  /**
   * The field storage definition listener.
   *
   * @var \Drupal\Core\Field\FieldStorageDefinitionListenerInterface
   */
  protected $fieldStorageDefinitionListener;

  /**
   * SocialCoreHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $entity_definition_update_manager
   *   The entity definition update manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $entity_last_installed_schema_repository
   *   The entity last installed schema repository.
   * @param \Drupal\Core\Entity\EntityTypeListenerInterface $entity_type_listener
   *   The entity type listener.
   * @param \Drupal\Core\Field\FieldStorageDefinitionListenerInterface $field_storage_definition_listener
   *   The field storage definition listener.
   */
  public function __construct(
    EntityDefinitionUpdateManagerInterface $entity_definition_update_manager,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    EntityLastInstalledSchemaRepositoryInterface $entity_last_installed_schema_repository,
    EntityTypeListenerInterface $entity_type_listener,
    FieldStorageDefinitionListenerInterface $field_storage_definition_listener
  ) {
    $this->entityDefinitionUpdateManager = $entity_definition_update_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityLastInstalledSchemaRepository = $entity_last_installed_schema_repository;
    $this->entityTypeListener = $entity_type_listener;
    $this->fieldStorageDefinitionListener = $field_storage_definition_listener;
  }

  /**
   * {@inheritdoc}
   */
  public function applyEntityUpdates($entity_type_id = NULL) {
    $complete_change_list = $this->entityDefinitionUpdateManager->getChangeList();

    if ($complete_change_list) {
      // In case there are changes, explicitly invalidate caches.
      $this->entityTypeManager->clearCachedDefinitions();
      $this->entityFieldManager->clearCachedFieldDefinitions();
    }

    if ($entity_type_id) {
      $complete_change_list = array_intersect_key($complete_change_list, [
        $entity_type_id => TRUE,
      ]);
    }

    foreach ($complete_change_list as $entity_type_id => $change_list) {
      // Process entity type definition changes before storage definitions ones
      // this is necessary when you change an entity type from non-revisionable
      // to revisionable and at the same time add revisionable fields to the
      // entity type.
      if (!empty($change_list['entity_type'])) {
        $this->doEntityUpdate($change_list['entity_type'], $entity_type_id);
      }

      // Process field storage definition changes.
      if (!empty($change_list['field_storage_definitions'])) {
        $storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
        $original_storage_definitions = $this->entityLastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($entity_type_id);

        foreach ($change_list['field_storage_definitions'] as $field_name => $change) {
          $storage_definition = $storage_definitions[$field_name] ?? NULL;
          $original_storage_definition = $original_storage_definitions[$field_name] ?? NULL;
          $this->doFieldUpdate($change, $storage_definition, $original_storage_definition);
        }
      }
    }
  }

  /**
   * Performs an entity type definition update.
   *
   * @param int $op
   *   The operation to perform, either static::DEFINITION_CREATED or
   *   static::DEFINITION_UPDATED.
   * @param string $entity_type_id
   *   The entity type ID.
   */
  protected function doEntityUpdate($op, $entity_type_id) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);

    switch ($op) {
      case EntityDefinitionUpdateManagerInterface::DEFINITION_CREATED:
        $this->entityTypeListener->onEntityTypeCreate($entity_type);
        break;

      case EntityDefinitionUpdateManagerInterface::DEFINITION_UPDATED:
        $original = $this->entityLastInstalledSchemaRepository->getLastInstalledDefinition($entity_type_id);
        $original_field_storage_definitions = $this->entityLastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($entity_type_id);

        $this->entityTypeListener->onFieldableEntityTypeUpdate($entity_type, $original, $field_storage_definitions, $original_field_storage_definitions);
        break;
    }
  }

  /**
   * Performs a field storage definition update.
   *
   * @param int $op
   *   The operation to perform, possible values are static::DEFINITION_CREATED,
   *   static::DEFINITION_UPDATED or static::DEFINITION_DELETED.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface|null $storage_definition
   *   The new field storage definition.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface|null $original_storage_definition
   *   The original field storage definition.
   */
  protected function doFieldUpdate($op, $storage_definition = NULL, $original_storage_definition = NULL) {
    switch ($op) {
      case EntityDefinitionUpdateManagerInterface::DEFINITION_CREATED:
        $this->fieldStorageDefinitionListener->onFieldStorageDefinitionCreate($storage_definition);
        break;

      case EntityDefinitionUpdateManagerInterface::DEFINITION_UPDATED:
        $this->fieldStorageDefinitionListener->onFieldStorageDefinitionUpdate($storage_definition, $original_storage_definition);
        break;

      case EntityDefinitionUpdateManagerInterface::DEFINITION_DELETED:
        $this->fieldStorageDefinitionListener->onFieldStorageDefinitionDelete($original_storage_definition);
        break;
    }
  }

}

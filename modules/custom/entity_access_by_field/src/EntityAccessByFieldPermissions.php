<?php

namespace Drupal\entity_access_by_field;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * EntityAccessByFieldPermissions.
 */
class EntityAccessByFieldPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

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
   * Constructs a new NodeViewPermissionsPermission instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Array with values which need to be ignored.
   *
   * @return array
   *   An array containing a list of values to ignore.
   */
  public static function getIgnoredValues() {
    return EntityAccessHelper::getIgnoredValues();
  }

  /**
   * Return all the permissions generated by this user.
   */
  public function permissions() {
    $permissions = [];

    $contentTypes = $this->getContentTypes();
    foreach ($contentTypes as $bundle) {
      $entity_type = 'node';
      $fields = $this->getEntityAccessFields($entity_type, $bundle);

      /** @var \Drupal\field\Entity\FieldConfig $field */
      foreach ($fields as $field) {
        $field_storage = $field->getFieldStorageDefinition();

        // Gets allowed values from function if exists.
        $function = $field_storage->getSetting('allowed_values_function');
        if (!empty($function)) {
          $allowed_values = $function($field_storage);
        }
        else {
          $allowed_values = $field_storage->getSetting('allowed_values');
        }

        if (!empty($allowed_values)) {
          foreach ($allowed_values as $field_key => $field_label) {
            if (!in_array($field_key, $this->getIgnoredValues())) {
              // e.g. label = node.article.field_content_visibility:public.
              $permission_label = "$entity_type.{$bundle->id()}.{$field->getName()}:$field_key";
              $permission = 'view ' . $permission_label . ' content';
              $permissions[$permission] = [
                'title' => $this->t('View @label content', ['@label' => $permission_label]),
              ];
            }
          }
        }
      }
    }

    return $permissions;
  }

  /**
   * Get the realms array with permissions as value.
   */
  public function getRealmWithPermission(): array {
  $realms = &drupal_static(__FUNCTION__);

  // If realms is not yet cached, let's populate it now.
  if (!isset($realms)) {
    $realms = [];
    $contentTypes = $this->getContentTypes();

    foreach ($contentTypes as $bundle) {
      $entity_type = 'node';
      $fields = $this->getEntityAccessFields($entity_type, $bundle);

      /** @var \Drupal\field\Entity\FieldConfig $field */
      foreach ($fields as $field) {
        $field_storage = $field->getFieldStorageDefinition();

        // Gets allowed values from function if exists.
        $function = $field_storage->getSetting('allowed_values_function');
        if (!empty($function)) {
          $allowed_values = $function($field_storage);
        }
        else {
          $allowed_values = $field_storage->getSetting('allowed_values');
        }

        if (!empty($allowed_values)) {
          $op = 'view';

          foreach ($allowed_values as $field_key => $field_label) {
            // e.g. label = node.article.field_content_visibility:public.
            $permission_label = "$entity_type.{$bundle->id()}.{$field->getName()}:$field_key";
            $permission = $op . ' ' . $permission_label . ' content';
            $bundle_id = $bundle->id();
            $field_name = $field->getName();
            $realm = $this->getRealmForFieldValue($op, $entity_type, $bundle_id, $field_name, $field_key);
            $realms[$realm] = $permission;
          }
        }
      }
    }
  }

    return $realms;
  }

  /**
   * Returns a realm for a field value in order to create access.
   *
   * @return string
   *   The string with the realm created.
   */
  public function getRealmForFieldValue($op, $entity_type, $bundle_id, $field_name, $field_value) {
    $realm = $op . '_' . $entity_type . '_' . $bundle_id . '_' . $field_name . '_' . $field_value;
    return $realm;
  }

  /**
   * Get all the content types.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Returns the entity interface containing all the content types.
   */
  protected function getContentTypes() {
    return $this->entityTypeManager->getStorage('node_type')->loadMultiple();
  }

  /**
   * Get all fields of type entity_access_field.
   *
   * @return array
   *   Returns all the fields with the entity type entity_acces_field.
   */
  public function getEntityAccessFields($entity, $bundle) {
    $fields = [];
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity, $bundle->id());
    foreach ($field_definitions as $field_name => $field_definition) {
      if ($field_definition->getType() === 'entity_access_field') {
        $fields[$field_name] = $field_definition;
      }
    }
    return $fields;
  }

}

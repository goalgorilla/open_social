<?php
/**
 * @file
 * Contains \Drupal\entity_access_by_field\EntityAccessByFieldPermissions.
 */

namespace Drupal\entity_access_by_field;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\UrlGeneratorTrait;

/**
 * EntityAccessByFieldPermissions
 */
class EntityAccessByFieldPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new NodeViewPermissionsPermission instance.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager'));
  }

  /**
   * Return all the permissions this module generates.
   */
  public function permissions() {
    $permissions = [];

    $contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
    foreach ($contentTypes as $bundle) {
      $entity_type = 'node';
      $fields = $this->getEntityAccessFields($entity_type, $bundle);

      /** @var \Drupal\field\Entity\FieldConfig $field */
      foreach ($fields as $field) {

        $field_storage = $field->getFieldStorageDefinition();
        // @TODO Add support for allowed_values_function.
        $allowed_values = $field_storage->getSetting('allowed_values');
        if (!empty($allowed_values)) {
          foreach ($allowed_values as $field_key => $field_label) {
            // e.g. label = node.article.field_content_visibility:public
            $permission_label = $field->id() . ':' . $field_key;
            $permission = 'view ' . $permission_label . ' content';
            $permissions[$permission] = [
              'title' => $this->t('View @label content', ['@label' => $permission_label]),
            ];
          }
        }
      }
    }

    return $permissions;
  }

  /**
   * Get all fields of type entity_access_field.
   *
   * @return array $fields
   */
  public function getEntityAccessFields($entity, $bundle) {
    $fields = [];
    $field_definitions = \Drupal::entityManager()->getFieldDefinitions($entity, $bundle->id());
    foreach ($field_definitions as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle())) {
        if ($field_definition->getType() === 'entity_access_field') {
          $fields[$field_name] = $field_definition;
        }
      }
    }
    return $fields;
  }

}
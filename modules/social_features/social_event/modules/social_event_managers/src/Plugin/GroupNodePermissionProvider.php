<?php

namespace Drupal\social_event_managers\Plugin;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\group\Plugin\GroupContentPermissionProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides group permissions for group_membership GroupContent entities.
 */
class GroupNodePermissionProvider extends GroupContentPermissionProvider {

  /**
   * The entity Field Manager object.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $plugin_id, array $definition) {
    $instance = parent::createInstance($container, $plugin_id, $definition);
    /** @var EntityFieldManagerInterface $entity_field_manager */
    $instance->entityFieldManager = $container->get('entity_field.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPermissions() {
    $permissions = parent::buildPermissions();

    $info = $this->definition; // Grab annotation information.

    $entity_id = $info['entity_type_id']; // hope this is correct haha, i think its in the annotation of the plugin.
    $bundle = $info['entity_bundle']; // hope this is correct aswell haha, i think its in the annotation of the plugin.

    $bundleFields = $this->entityFieldManager->getFieldDefinitions($entity_id, $bundle);
    if ($entity_id === 'node' && isset($bundleFields['field_content_visibility'])) {
      $field_config = $bundleFields['field_content_visibility']; // grab all it from here.
      $allowed_options = $field_config->getFieldStorageDefinition()->getSetting('allowed_values');

      if (array_key_exists('group', $allowed_options)) {
        $key_name = "view eabf $entity_id.$bundle.field_content_visibility:group content";
        $permissions[$key_name] = [
            'title' => "Content visibility: View $entity_id.$bundle content",
        ];
      }
    }

    return $permissions;
  }

}

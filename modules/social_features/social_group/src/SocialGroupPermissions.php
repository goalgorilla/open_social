<?php

namespace Drupal\social_group;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Entity\FieldConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the SocialGroupPermissions service.
 *
 * This service generates dynamic permissions for groups based on their bundle
 * and visibility. It utilizes entity type and field managers to retrieve
 * relevant data and construct permissions accordingly.
 */
class SocialGroupPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Constructs a new SocialGroupPermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $entityFieldManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
    );
  }

  /**
   * Generates a list of dynamic permissions for group visibility.
   *
   * This method creates permissions based on the available visibilities for
   * groups and their specific bundles. Each permission is dynamically
   * constructed to allow granular control over viewing certain group types
   * and visibilities.
   *
   * @return array
   *   An associative array of permissions, where the key is the permission
   *   machine name (e.g., "view community flexible_group group") and the value
   *   is an array containing the 'title' of the permission.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function permissions(): array {
    $allowed_visibilities = SocialGroupHelperService::getAvailableVisibilities();

    if (empty($allowed_visibilities)) {
      return [];
    }

    $visibility_fields = array_filter(FieldConfig::loadMultiple(),
      fn ($field_instance) => $field_instance->getName() === 'field_flexible_group_visibility' &&
        $field_instance->getTargetEntityTypeId() === 'group'
    );

    foreach ($visibility_fields as $visibility_field) {
      foreach ($allowed_visibilities as $visibility) {
        $bundle = $visibility_field->getTargetBundle();
        /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
        $group_type = $this->entityTypeManager
          ->getStorage('group_type')
          ->load($bundle);

        // Build a permission, like "view community flexible_group group".
        $permission = 'view ' . $visibility . ' ' . $bundle . ' group';

        $permissions[$permission] = [
          'title' => $group_type->label() . ': view ' . $visibility . ' group',
        ];
      }
    }

    return $permissions ?? [];
  }

}

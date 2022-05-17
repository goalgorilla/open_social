<?php

namespace Drupal\social_flexible_group_book;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a helper class, sets permissions.
 */
class SocialFlexibleGroupBookInstallHelper implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs SocialFlexibleGroupBookInstallHelper.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Set default permissions for Book Page content in flexible group.
   */
  public function setGroupPermissions(): void {
    /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
    $group_type = $this->entityTypeManager
      ->getStorage('group_type')
      ->load('flexible_group');

    foreach ($group_type->getRoles() as $role) {
      $group_permissions = $this->getGroupPermissions((string) $role->id());

      if (!empty($group_permissions)) {
        $role->grantPermissions($group_permissions)
          ->save();
      }
    }
  }

  /**
   * Revoke default permissions for Book Page content in flexible group.
   */
  public function revokeGroupPermissions(): void {
    /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
    $group_type = $this->entityTypeManager
      ->getStorage('group_type')
      ->load('flexible_group');

    foreach ($group_type->getRoles() as $role) {
      $group_permissions = $this->getGroupPermissions((string) $role->id());

      if (!empty($group_permissions)) {
        $role->revokePermissions($group_permissions)
          ->save();
      }
    }
  }

  /**
   * Set default permissions for Book Page content in flexible group.
   */
  public function getGroupPermissions(string $role): array {
    // Group member.
    $group_permissions['flexible_group-member'] = [
      'view group_node:book content',
      'view group_node:book entity',
    ];

    // Group manager.
    $group_permissions['flexible_group-group_manager'] = [
      ...$group_permissions['flexible_group-member'],
      ...[
        'create group_node:book content',
        'create group_node:book entity',
        'delete own group_node:book entity',
        'delete own group_node:book content',
        'update own group_node:book content',
        'update own group_node:book entity',
      ],
    ];
    // Group outside role: Content manager.
    $group_permissions['flexible_group-83776d798'] = $group_permissions['flexible_group-group_manager'];

    // Group admin.
    $group_permissions['flexible_group-group_admin'] = [
      ...$group_permissions['flexible_group-group_manager'],
      ...[
        'delete any group_node:book content',
        'delete any group_node:book entity',
        'update any group_node:book content',
        'update any group_node:book entity',
        'view unpublished group_node:book entity',
      ],
    ];

    // Group outside role: Site manager.
    $group_permissions['flexible_group-ba5854c29'] = $group_permissions['flexible_group-group_admin'];

    // Group outside role: Administrator.
    $group_permissions['flexible_group-a416e6833'] = $group_permissions['flexible_group-group_admin'];

    return $group_permissions[$role] ?? [];
  }

}

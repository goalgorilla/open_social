<?php

namespace Drupal\grequest\Plugin\Group\RelationHandler;

use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface;
use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderTrait;

/**
 * Provides group permissions for the group_membership_request relation plugin.
 */
class GroupMembershipRequestPermissionProvider implements PermissionProviderInterface {

  use PermissionProviderTrait;

  /**
   * Constructs a new GroupMembershipPermissionProvider.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface $parent
   *   The default permission provider.
   */
  public function __construct(PermissionProviderInterface $parent) {
    $this->parent = $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermission($operation, $target, $scope = 'any') {
    if (!isset($this->parent)) {
      throw new \LogicException('Using PermissionProviderTrait without assigning a parent or overwriting the methods.');
    }

    // The following permissions are handled by the admin permission or have a
    // different permission name.
    if ($target === 'relationship') {
      switch ($operation) {
        case 'view':
          return "view $scope $this->pluginId $target";

        case 'update':
        case 'create':
        case 'delete':
          return $this->getAdminPermission();
      }
    }
    return $this->parent->getPermission($operation, $target, $scope);
  }

  /**
   * {@inheritdoc}
   */
  public function buildPermissions() {
    if (!isset($this->parent)) {
      throw new \LogicException('Using PermissionProviderTrait without assigning a parent or overwriting the methods.');
    }
    $permissions = $this->parent->buildPermissions();

    // Update the title to make user friendly.
    $permissions[$this->getAdminPermission()]['title'] = 'Administer membership requests';

    $permissions[$this->getRequestGroupMembershipPermission()] = [
      'title' => 'Request group membership',
      'allowed for' => ['outsider'],
    ];

    $permissions[$this->getPermission('view', 'relationship')]['title'] = 'View any membership requests';
    $permissions[$this->getPermission('view', 'relationship', 'own')]['title'] = 'View own membership requests';

    return $permissions;
  }

  /**
   * Get request membership permission.
   *
   * @return string
   *  Permission name.
   */
  public function getRequestGroupMembershipPermission() {
    return 'request group membership';
  }

}

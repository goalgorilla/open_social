<?php

namespace Drupal\grequest\Plugin\Group\RelationHandler;

use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface;
use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderTrait;

/**
 * Provides group permissions for the grequest relation plugin.
 */
class GroupMembershipRequestPermissionProvider implements PermissionProviderInterface {

  use PermissionProviderTrait;

  /**
   * Constructs a new GroupMembershipRequestPermissionProvider.
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

    // Add extra permissions specific to membership group content entities.
    $permissions[$this->getRequestGroupMembershipPermission()] = [
      'title' => 'Request group membership',
      'allowed for' => ['outsider'],
    ];

    return $permissions;
  }

  /**
   * Get request membership permission.
   *
   * @return string
   *   Permission name.
   */
  public function getRequestGroupMembershipPermission() {
    return 'request group membership';
  }

}

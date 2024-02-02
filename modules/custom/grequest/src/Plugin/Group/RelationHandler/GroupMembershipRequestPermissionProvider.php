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
    $permissions = $this->parent->buildPermissions();

    // Update the title to make user friendly.
    $permissions[$this->getAdminPermission()]['title'] = $this->t('Administer membership requests');

    // Add extra permissions specific to membership group content entities.
    $permissions[$this->getRequestGroupMembershipPermission()] = [
      'title' => $this->t('Request group membership'),
      'allowed for' => ['outsider'],
    ];

    // These are handled by 'administer members'.
    unset($permissions['update own group_membership_request content']);
    unset($permissions['view group_membership_request content']);
    unset($permissions['create group_membership_request content']);
    unset($permissions['update any group_membership_request content']);
    unset($permissions['delete any group_membership_request content']);
    unset($permissions['delete own group_membership_request content']);

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

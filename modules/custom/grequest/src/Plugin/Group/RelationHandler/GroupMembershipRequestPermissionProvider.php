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
    $operations = [
      'view',
      'create',
      'update'
      'delete',
    ];

    // @todo: check $target and if needed additinalol permissions.
    if ($target === 'relationship') {
      if (in_array($operation, $operations) {
        return 'administer members';
      }
    }
    return $this->parent->getPermission($operation, $target, $scope);
  }

  /**
   * {@inheritdoc}
   */
  public function buildPermissions() {
    $permissions = $this->parent->buildPermissions();

    // Add extra permissions specific to membership group content entities.
    $permissions['request group membership'] = [
      'title' => $this->t('Request group membership!'),
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

}

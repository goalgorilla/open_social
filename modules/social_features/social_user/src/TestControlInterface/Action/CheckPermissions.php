<?php

declare(strict_types=1);

namespace Drupal\social_user\TestControlInterface\Action;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\social_user\TestControlInterface\Error\PermissionMissing;
use Drupal\social_user\TestControlInterface\Error\UnexpectedPermission;
use Drupal\social_user\TestControlInterface\Success\PermissionCheckSuccess;
use Drupal\test_control_interface\Attribute\Example;
use Drupal\test_control_interface\Attribute\Operation;
use Drupal\test_control_interface\Enum\OperationType;

/**
 * TCI endpoints asserting the current user's permissions.
 *
 * Replaces RoleContext::iExpectToHaveThePermission() and
 * RoleContext::iDoNotExpectToHaveThePermission(). Those read
 * \Drupal::currentUser() inside the Behat process; here the request's
 * current user is resolved the same way as for any other TCI operation
 * (session cookie, or the TCI current-user header naming whoever Behat is
 * logged in as — see
 * Drupal\test_control_interface\EventSubscriber\CurrentUserSwitchSubscriber),
 * so both sides mean the same "current user".
 */
final class CheckPermissions {

  public function __construct(
    private readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * Asserts that the current user has the given permission.
   *
   * @param string $permission
   *   The permission machine name to check.
   *
   * @return \Drupal\social_user\TestControlInterface\Success\PermissionCheckSuccess|\Drupal\social_user\TestControlInterface\Error\PermissionMissing
   *   The operation result.
   */
  #[Operation(
    id: 'current_user_permission_expected',
    patterns: ['I expect to have the permission :permission'],
    type: OperationType::Read,
  )]
  public function expectPermission(
    #[Example('administer user_segment')]
    string $permission,
  ): PermissionCheckSuccess|PermissionMissing {
    if (!$this->currentUser->hasPermission($permission)) {
      return new PermissionMissing($permission);
    }

    return new PermissionCheckSuccess(permission: $permission, uid: (int) $this->currentUser->id());
  }

  /**
   * Asserts that the current user does not have the given permission.
   *
   * @param string $permission
   *   The permission machine name to check.
   *
   * @return \Drupal\social_user\TestControlInterface\Success\PermissionCheckSuccess|\Drupal\social_user\TestControlInterface\Error\UnexpectedPermission
   *   The operation result.
   */
  #[Operation(
    id: 'current_user_permission_not_expected',
    patterns: ['I do not expect to have the permission :permission'],
    type: OperationType::Read,
  )]
  public function expectNoPermission(
    #[Example('administer user_segment')]
    string $permission,
  ): PermissionCheckSuccess|UnexpectedPermission {
    if ($this->currentUser->hasPermission($permission)) {
      return new UnexpectedPermission($permission);
    }

    return new PermissionCheckSuccess(permission: $permission, uid: (int) $this->currentUser->id());
  }

}

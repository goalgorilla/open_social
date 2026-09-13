<?php

declare(strict_types=1);

namespace Drupal\social_user\TestControlInterface\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\Session\UserSession;
use Drupal\social_user\TestControlInterface\Success\RolePermissionsGrantedSuccess;
use Drupal\test_control_interface\Attribute\Example;
use Drupal\test_control_interface\Attribute\Operation;
use Drupal\test_control_interface\Enum\OperationType;
use Drupal\user\Entity\Role;
use Drupal\user\PermissionHandlerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * TCI endpoint for role permission grants.
 *
 * Replaces RoleContext::iHaveCustomRoleWithPermission().
 */
final class GrantRolePermissions {

  public function __construct(
    private readonly PermissionHandlerInterface $permissionHandler,
    private readonly AccountSwitcherInterface $accountSwitcher,
  ) {
  }

  /**
   * Creates a role with a random machine name and grants it permissions.
   *
   * @return \Drupal\social_user\TestControlInterface\Success\RolePermissionsGrantedSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'role_permission_grant',
    patterns: ['I have custom role :role with :permissions permission(s)'],
    type: OperationType::Write,
  )]
  public function grantRolePermissions(
    #[Example('Content Manager')]
    string $role,
    #[Example('access content overview, administer nodes')]
    string $permissions,
  ): RolePermissionsGrantedSuccess {
    // Inserting a role can re-save other roles: with social_role_delegation
    // enabled, its insert hook grants the site manager an "assign <role>
    // role" permission. Core then removes any permission of that re-saved
    // role it doesn't know. The role visibility permissions (e.g. cm_plus)
    // only exist when _social_role_visibility_allowed_role_visibility_options()
    // lists their options, which it does under PHP_SAPI === 'cli' (where the
    // original Behat step ran) or for privileged users. This operation runs in
    // PHP-FPM as the request's current user (often anonymous, e.g. in a
    // Background before any login), so the site manager would lose those
    // permissions. Run as a site manager, who sees every option, and always
    // switch back so the rest of the request keeps its own user.
    $this->accountSwitcher->switchTo(new UserSession([
      'roles' => [AccountInterface::AUTHENTICATED_ROLE, 'sitemanager'],
    ]));
    try {
      $rid = mb_strtolower(substr(bin2hex(random_bytes(8)), 0, 8));
      $roleEntity = Role::create(['id' => $rid, 'label' => $role]);
      $roleEntity->save();

      $requested = array_map('trim', explode(',', $permissions));
      $available = $this->permissionHandler->getPermissions();
      $machineNames = [];
      foreach ($requested as $name) {
        $machineName = $name;
        foreach ($available as $permissionId => $definition) {
          if ((string) $definition['title'] === $name) {
            $machineName = $permissionId;
            break;
          }
        }
        if (!isset($available[$machineName])) {
          throw new BadRequestHttpException(sprintf("Invalid permission '%s'.", $name));
        }
        $machineNames[] = $machineName;
      }

      $roleId = (string) $roleEntity->id();
      user_role_grant_permissions($roleId, $machineNames);
    }
    finally {
      $this->accountSwitcher->switchBack();
    }

    return new RolePermissionsGrantedSuccess(roleId: $roleId);
  }

}

<?php

declare(strict_types=1);

namespace Drupal\social_user\TestControlInterface\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\Session\UserSession;
use Drupal\social_user\TestControlInterface\Success\RolesCreatedSuccess;
use Drupal\test_control_interface\Attribute\Example;
use Drupal\test_control_interface\Attribute\Operation;
use Drupal\test_control_interface\Enum\OperationType;
use Drupal\user\Entity\Role;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * TCI endpoint for role creation.
 *
 * Replaces RoleContext::createRoles().
 */
final class CreateRoles {

  public function __construct(
    private readonly AccountSwitcherInterface $accountSwitcher,
  ) {
  }

  /**
   * Creates multiple roles from a table; one row per role.
   *
   * @param list<\Drupal\social_user\TestControlInterface\Dto\CreateRoleRequest> $body
   *   The list of roles to create.
   *
   * @return \Drupal\social_user\TestControlInterface\Success\RolesCreatedSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'role_create_bulk',
    patterns: ['roles:'],
    type: OperationType::Write,
  )]
  public function create(
    #[Example([
      ['label' => 'My title'],
    ])]
    array $body,
  ): RolesCreatedSuccess {
    if ($body === []) {
      throw new BadRequestHttpException('No role rows in request body; refusing to return success with zero roles created.');
    }

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
      $ids = [];
      foreach ($body as $row) {
        $id = $row->id ?? mb_strtolower($row->label);
        $role = Role::create([
          'id' => $id,
          'label' => $row->label,
        ]);
        $role->save();
        $ids[] = (string) $role->id();
      }
    }
    finally {
      $this->accountSwitcher->switchBack();
    }

    return new RolesCreatedSuccess(ids: $ids);
  }

}

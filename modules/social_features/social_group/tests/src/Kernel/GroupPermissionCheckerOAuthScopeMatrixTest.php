<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group\Kernel;

use Drupal\consumers\Entity\Consumer;
use Drupal\group\Access\GroupPermissionCheckerInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupRole;
use Drupal\group\Entity\GroupType;
use Drupal\group\PermissionScopeInterface;
use Drupal\simple_oauth\Authentication\TokenAuthUser;
use Drupal\simple_oauth\Entity\Oauth2Token;
use Drupal\Tests\group\Kernel\GroupKernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Ensures the decorated group permission checker applies the full OAuth matrix.
 *
 * When these tests pass, the checker correctly combines permissions from OAuth
 * scopes with user permissions from Drupal Access Policy by application type
 * (bot vs user) and respects permission-level scopes (individual, insider,
 * outsider) for both the application and the user. For user-type apps, access
 * is the intersection of user access and app access.
 *
 * App membership in the group is not covered (app is treated as non-member).
 * Insider/outsider refer to application context
 * (\Drupal\social_oauth\Service\ApplicationGroupContextInterface),
 * not app-as-group-member.
 *
 * @group group
 */
class GroupPermissionCheckerOAuthScopeMatrixTest extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity',
    'flexible_permissions',
    'group',
    'options',
    'serialization',
    'simple_oauth',
    'simple_oauth_static_scope',
    'consumers',
    'user',
    'file',
    'image',
    'flag',
    'social_group',
    'social_oauth',
    'social_group_test_scope_matrix',
  ];

  /**
   * Group type id used across scenarios.
   */
  private const GROUP_TYPE_ID = 'example_group';

  /**
   * Permission checked in matrix scenarios.
   */
  private const PERMISSION_GROUP_VIEW = 'view group';

  /**
   * Permission granted on group roles (used in createGroupTypeWithRoles).
   */
  private const PERMISSION_GROUP_EDIT = 'edit group';

  /**
   * The group permission checker (decorated with OAuth support).
   *
   * @var \Drupal\group\Access\GroupPermissionCheckerInterface
   */
  protected GroupPermissionCheckerInterface $groupPermissionChecker;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('consumer');
    $this->installEntitySchema('oauth2_token');
    $this->installConfig(['simple_oauth', 'consumers']);

    $this->config('simple_oauth.settings')
      ->set('scope_provider', 'static')
      ->save();

    $this->groupPermissionChecker = $this->container->get('group_permission.checker');
  }

  /**
   * Data provider for the full OAuth group permission scenario matrix.
   *
   * Logic matrix (outcome = user has access AND app has access for User type):
   *
   * | App   | App perm.  | App in | User perm. | User   | Outcome |
   * | type  | scope      | group  | scope      | member |         |
   * | ------|------------|--------|------------|--------|---------|
   * | Bot   | individual |        |            |        | ALLOW   |
   * | Bot   | insider    | no     |            |        | DENY    |
   * | Bot   | outsider   | no     |            |        | ALLOW   |
   * | User  | individual |        | individual |        | ALLOW   |
   * | User  | individual |        | insider    | yes    | ALLOW   |
   * | User  | individual |        | insider    | no     | DENY    |
   * | User  | individual |        | outsider   | yes    | DENY    |
   * | User  | individual |        | outsider   | no     | ALLOW   |
   * | User  | insider    | no     | individual |        | DENY    |
   * | User  | insider    | no     | insider    | yes    | DENY    |
   * | User  | insider    | no     | insider    | no     | DENY    |
   * | User  | insider    | no     | outsider   | yes    | DENY    |
   * | User  | insider    | no     | outsider   | no     | DENY    |
   * | User  | outsider   | no     | individual |        | ALLOW   |
   * | User  | outsider   | no     | insider    | yes    | ALLOW   |
   * | User  | outsider   | no     | insider    | no     | DENY    |
   * | User  | outsider   | no     | outsider   | yes    | DENY    |
   * | User  | outsider   | no     | outsider   | no     | ALLOW   |
   *
   * Empty cells mean that value doesn't matter for the outcome. For User app
   * type, outcome is the intersection of user
   * access and app access. App "in group" is not implemented (assumed no).
   *
   * Feature scenario mapping (same order as table):
   * 1-3:   Bot application in individual/insider/outsider context of group with
   *        permission from scope
   * 4-8:   User application in individual context ...
   *        with user in individual/insider (member|not)/outsider (member|not)
   * 9-13:  User application in insider context ... (app hardcoded not in group)
   * 14-18: User application in outsider context ... (app not in group)
   *
   * @return array[]
   *   Each row: scenario name, is_bot, scope_id, user_is_member,
   *   user_has_individual_permission, user_has_insider_permission,
   *   user_has_outsider_permission, expected_allowed.
   */
  public static function scenarioMatrixProvider(): array {
    return [

      // Bot: outsider and individual scopes grant permission;
      // insider does not (defaults to FALSE).
      'Bot application with scope test:group:individual' => [
        TRUE, 'test:group:individual', FALSE, FALSE, FALSE, FALSE, TRUE,
      ],
      'Bot application with scope test:group:insider' => [
        TRUE, 'test:group:insider', FALSE, FALSE, FALSE, FALSE, FALSE,
      ],
      'Bot application with scope test:group:outsider' => [
        TRUE, 'test:group:outsider', FALSE, FALSE, FALSE, FALSE, TRUE,
      ],

      // User application: application in individual context.
      'User application in individual context with user in individual context of group' => [
        FALSE, 'test:group:individual', TRUE, TRUE, FALSE, FALSE, TRUE,
      ],
      'User application in individual context with user in insider context of group and being a member' => [
        FALSE, 'test:group:individual', TRUE, FALSE, TRUE, FALSE, TRUE,
      ],
      'User application in individual context with user in insider context of group and not being a member' => [
        FALSE, 'test:group:individual', FALSE, FALSE, FALSE, FALSE, FALSE,
      ],
      'User application in individual context with user in outsider context of group and being a member' => [
        FALSE, 'test:group:individual', TRUE, FALSE, FALSE, TRUE, FALSE,
      ],
      'User application in individual context with user in outsider context of group and not being a member' => [
        FALSE, 'test:group:individual', FALSE, FALSE, FALSE, TRUE, TRUE,
      ],

      // User application: application in insider context.
      // Token does not grant insider (defaults to FALSE).
      'User application in insider context with user in individual context of group' => [
        FALSE, 'test:group:insider', TRUE, TRUE, FALSE, FALSE, FALSE,
      ],
      'User application in insider context with user in insider context of group and being a member' => [
        FALSE, 'test:group:insider', TRUE, FALSE, TRUE, FALSE, FALSE,
      ],
      'User application in insider context with user in insider context of group and not being a member' => [
        FALSE, 'test:group:insider', FALSE, FALSE, FALSE, FALSE, FALSE,
      ],
      'User application in insider context with user in outsider context of group and being a member' => [
        FALSE, 'test:group:insider', TRUE, FALSE, FALSE, TRUE, FALSE,
      ],
      'User application in insider context with user in outsider context of group and not being a member' => [
        FALSE, 'test:group:insider', FALSE, FALSE, FALSE, TRUE, FALSE,
      ],

      // User application: application in outsider context.
      'User application in outsider context with user in individual context of group' => [
        FALSE, 'test:group:outsider', TRUE, TRUE, FALSE, FALSE, TRUE,
      ],
      'User application in outsider context with user in insider context of group and being a member' => [
        FALSE, 'test:group:outsider', TRUE, FALSE, TRUE, FALSE, TRUE,
      ],
      'User application in outsider context with user in insider context of group and not being a member' => [
        FALSE, 'test:group:outsider', FALSE, FALSE, FALSE, FALSE, FALSE,
      ],
      'User application in outsider context with user in outsider context of group and being a member' => [
        FALSE, 'test:group:outsider', TRUE, FALSE, FALSE, TRUE, FALSE,
      ],
      'User application in outsider context with user in outsider context of group and not being a member' => [
        FALSE, 'test:group:outsider', FALSE, FALSE, FALSE, TRUE, TRUE,
      ],
    ];
  }

  /**
   * Tests the full scenario matrix for OAuth group permission checks.
   *
   * @param bool $is_bot
   *   TRUE if the token is a bot (client_credentials, no user).
   * @param string $scope_id
   *   OAuth scope id (test:group:outsider, test:group:insider,
   *   test:group:individual).
   * @param bool $user_is_member
   *   Whether the user is a member of the group.
   * @param bool $user_has_individual_permission
   *   Whether the user has the permission via individual (per-group) role.
   * @param bool $user_has_insider_permission
   *   Whether the user has the permission via insider (synchronized) role.
   * @param bool $user_has_outsider_permission
   *   Whether the user has the permission via outsider (synchronized) role.
   * @param bool $expected_allowed
   *   Whether access is expected to be allowed.
   *
   * @dataProvider scenarioMatrixProvider
   */
  public function testOauthScopeMatrix(
    bool $is_bot,
    string $scope_id,
    bool $user_is_member,
    bool $user_has_individual_permission,
    bool $user_has_insider_permission,
    bool $user_has_outsider_permission,
    bool $expected_allowed,
  ): void {
    $this->createGroupTypeWithRoles();
    // Individual scope in the test matrix is defined with identifier 1 (group
    // ID). Ensure the group we check has id 1 by creating it as the first
    // group.
    $group = Group::create([
      'type' => self::GROUP_TYPE_ID,
      'label' => 'Test Group',
    ]);
    $group->save();

    $user = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
    ]);
    if ($user_has_insider_permission) {
      $user->addRole('example_insider');
    }
    if ($user_has_outsider_permission) {
      $user->addRole('example_outsider');
    }
    $user->save();
    // Individual role requires membership
    // (Group module: roles are on the membership).
    if ($user_has_individual_permission) {
      $group->addMember($user, ['group_roles' => [self::GROUP_TYPE_ID . '-individual-editor']]);
    }
    elseif ($user_is_member) {
      $group->addMember($user, []);
    }

    $consumer = Consumer::create([
      'client_id' => 'test_client_' . $this->randomMachineName(8),
      'label' => 'Test Client',
      'secret' => 'test_secret',
      'grant_types' => $is_bot ? ['client_credentials'] : ['authorization_code'],
      'scopes' => [$scope_id],
      // Bot tokens need Consumer to have a user_id so TokenAuthUser can
      // construct (subject fallback).
      'user_id' => $user->id(),
    ]);
    $consumer->save();

    $token_values = [
      'bundle' => 'access_token',
      'client' => $consumer,
      'scopes' => [$scope_id],
      'value' => 'test_token_' . $this->randomMachineName(16),
      'expire' => \Drupal::time()->getRequestTime() + 3600,
      'status' => TRUE,
    ];
    // Bot (client_credentials) tokens have no user; user tokens carry
    // auth_user_id. Oauth2Token defaults auth_user_id to current user, so we
    // must set it explicitly.
    if ($is_bot) {
      $token_values['auth_user_id'] = NULL;
    }
    else {
      $token_values['auth_user_id'] = $user->id();
    }
    $token = Oauth2Token::create($token_values);
    $token->save();

    $token_auth_user = new TokenAuthUser($token);

    $result = $this->groupPermissionChecker->hasPermissionInGroup(
      self::PERMISSION_GROUP_VIEW,
      $token_auth_user,
      $group
    );

    $this->assertSame($expected_allowed, $result, "Scenario expected " . ($expected_allowed ? 'allowed' : 'denied') . ".");
  }

  /**
   * Creates the example_group type and roles used by the matrix.
   *
   * Group roles (where the permissions are):
   *
   * @code
   * | Group role                       | Scope      | Global Drupal role     | Group membership | Permissions            |
   * |----------------------------------|------------|------------------------|------------------|------------------------|
   * | example_group-outsider-editor    | outsider   | example_outsider       | Not a member     | view group, edit group |
   * | example_group-insider-editor     | insider    | example_insider        | Member           | view group, edit group |
   * | example_group-individual-editor  | individual | (none, per-membership) | Member           | view group, edit group |
   * @endcode
   *
   * Outsider: permissions apply when the user is not a group member.
   * Insider: user must be a member; the global role grants the insider group
   * role.
   * Individual: user must be a member; the role is assigned on the membership.
   */
  private function createGroupTypeWithRoles(): GroupType {
    $group_type = GroupType::create([
      'id' => self::GROUP_TYPE_ID,
      'label' => 'Example Group Type',
      'creator_wizard' => FALSE,
    ]);
    $group_type->save();

    // Global roles that map to insider/outsider group roles.
    foreach (['example_outsider', 'example_insider'] as $role_id) {
      if (\Drupal::entityTypeManager()->getStorage('user_role')->load($role_id) === NULL) {
        $role = Role::create([
          'id' => $role_id,
          'label' => ucfirst(str_replace('_', ' ', $role_id)),
        ]);
        $role->grantPermission('access content');
        $role->save();
      }
    }

    // Outsider role: not a member, has view group and edit group.
    $outsider_role = GroupRole::create([
      'id' => self::GROUP_TYPE_ID . '-outsider-editor',
      'label' => 'Example outsider editor',
      'group_type' => self::GROUP_TYPE_ID,
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => 'example_outsider',
    ]);
    $outsider_role
      ->grantPermission(self::PERMISSION_GROUP_VIEW)
      ->grantPermission(self::PERMISSION_GROUP_EDIT)
      ->save();

    // Insider role: member, has view group and edit group.
    $insider_role = GroupRole::create([
      'id' => self::GROUP_TYPE_ID . '-insider-editor',
      'label' => 'Example insider editor',
      'group_type' => self::GROUP_TYPE_ID,
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => 'example_insider',
    ]);
    $insider_role
      ->grantPermission(self::PERMISSION_GROUP_VIEW)
      ->grantPermission(self::PERMISSION_GROUP_EDIT)
      ->save();

    // Individual role: per-group membership role with view group and edit
    // group.
    $individual_role = GroupRole::create([
      'id' => self::GROUP_TYPE_ID . '-individual-editor',
      'label' => 'Example individual editor',
      'group_type' => self::GROUP_TYPE_ID,
      'scope' => PermissionScopeInterface::INDIVIDUAL_ID,
    ]);
    $individual_role
      ->grantPermission(self::PERMISSION_GROUP_VIEW)
      ->grantPermission(self::PERMISSION_GROUP_EDIT)
      ->save();

    return $group_type;
  }

}

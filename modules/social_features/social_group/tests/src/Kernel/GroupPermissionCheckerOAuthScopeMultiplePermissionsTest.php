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
 * Tests that a single OAuth scope can grant multiple permissions.
 *
 * Verifies that when an OAuth2 scope defines multiple permissions (Drupal
 * scope and group Access Policy scopes), all are resolved and applied
 * correctly (for bot: all from scope; for user: intersection with user).
 *
 * @group group
 */
class GroupPermissionCheckerOAuthScopeMultiplePermissionsTest extends GroupKernelTestBase {

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
    'social_group_test_scope_multiple_permissions',
  ];

  private const GROUP_TYPE_ID = 'example_group';

  private const PERMISSION_GROUP_VIEW = 'view group';

  private const PERMISSION_GROUP_EDIT = 'edit group';

  private const PERMISSION_DRUPAL = 'access content';

  /**
   * Global role that grants only the Drupal permission (no group role).
   */
  private const ROLE_CONTENT_ACCESS_ONLY = 'test_content_access_only';

  private const ROLE_EXAMPLE_OUTSIDER = 'test_example_outsider';

  private const ROLE_EXAMPLE_INSIDER = 'test_example_insider';

  private const ROLE_EXAMPLE_OUTSIDER_VIEW_ONLY = 'test_example_outsider_view_only';

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
   * Creates the example_group type and roles used by multi-permission test.
   *
   * Global Drupal roles (created first): test_example_outsider and
   * test_example_insider, each granted PERMISSION_DRUPAL ('access content').
   *
   * Group roles created by this method:
   *
   * @code
   * | Group role                      | Scope      | Global Drupal role     | Permissions            |
   * |---------------------------------|------------|------------------------|------------------------|
   * | example_group-outsider-editor   | outsider   | test_example_outsider  | view group, edit group |
   * | example_group-insider-editor    | insider    | test_example_insider   | view group, edit group |
   * | example_group-individual-editor | individual | (none, per-membership) | view group, edit group |
   * @endcode
   *
   * Tests that need a view-only outsider role (e.g. testUserWithViewOnly...)
   * create example_group-outsider-view-only and test_example_outsider_view_only
   * themselves.
   */
  private function createGroupTypeWithRoles(): GroupType {
    $group_type = GroupType::create([
      'id' => self::GROUP_TYPE_ID,
      'label' => 'Example Group Type',
      'creator_wizard' => FALSE,
    ]);
    $group_type->save();

    foreach ([self::ROLE_EXAMPLE_OUTSIDER, self::ROLE_EXAMPLE_INSIDER] as $role_id) {
      if (\Drupal::entityTypeManager()->getStorage('user_role')->load($role_id) === NULL) {
        $role = Role::create([
          'id' => $role_id,
          'label' => ucfirst(str_replace('_', ' ', $role_id)),
        ]);
        $role->grantPermission(self::PERMISSION_DRUPAL);
        $role->save();
      }
    }

    $outsider_role = GroupRole::create([
      'id' => self::GROUP_TYPE_ID . '-outsider-editor',
      'label' => 'Example outsider editor',
      'group_type' => self::GROUP_TYPE_ID,
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => self::ROLE_EXAMPLE_OUTSIDER,
    ]);
    $outsider_role
      ->grantPermission(self::PERMISSION_GROUP_VIEW)
      ->grantPermission(self::PERMISSION_GROUP_EDIT)
      ->save();

    $insider_role = GroupRole::create([
      'id' => self::GROUP_TYPE_ID . '-insider-editor',
      'label' => 'Example insider editor',
      'group_type' => self::GROUP_TYPE_ID,
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => self::ROLE_EXAMPLE_INSIDER,
    ]);
    $insider_role
      ->grantPermission(self::PERMISSION_GROUP_VIEW)
      ->grantPermission(self::PERMISSION_GROUP_EDIT)
      ->save();

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

  /**
   * Tests that bot with multi-permission scope receives all scope permissions.
   */
  public function testBotWithMultiPermissionScopeGetsAllPermissions(): void {
    $this->createGroupTypeWithRoles();
    $group = Group::create([
      'type' => self::GROUP_TYPE_ID,
      'label' => 'Test Group Multi',
    ]);
    $group->save();

    $user_bot = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
    ]);
    $user_bot->save();
    $consumer_bot = Consumer::create([
      'client_id' => 'test_multi_bot',
      'label' => 'Test Multi Bot',
      'secret' => 'secret',
      'grant_types' => ['client_credentials'],
      'scopes' => ['test:group:multi_outsider'],
      'user_id' => $user_bot->id(),
    ]);
    $consumer_bot->save();

    $token_bot = Oauth2Token::create([
      'bundle' => 'access_token',
      'client' => $consumer_bot,
      'scopes' => ['test:group:multi_outsider'],
      'value' => 'token_bot_multi',
      'expire' => \Drupal::time()->getRequestTime() + 3600,
      'status' => TRUE,
      'auth_user_id' => NULL,
    ]);
    $token_bot->save();

    $token_auth_bot = new TokenAuthUser($token_bot);

    $this->assertTrue(
      $this->groupPermissionChecker->hasPermissionInGroup(self::PERMISSION_GROUP_VIEW, $token_auth_bot, $group),
      'Bot with multi-permission scope should have view group.'
    );
    $this->assertTrue(
      $this->groupPermissionChecker->hasPermissionInGroup(self::PERMISSION_GROUP_EDIT, $token_auth_bot, $group),
      'Bot with multi-permission scope should have edit group.'
    );
    $this->assertTrue(
      $token_auth_bot->hasPermission(self::PERMISSION_DRUPAL),
      'Bot with multi-permission scope should have Drupal permission access content.'
    );
  }

  /**
   * Tests that a user with both group and multi-permission scope gets all.
   *
   * Intersection of scope (view, edit, access content) and user (view, edit,
   * access content) allows view group, edit group and Drupal access content.
   */
  public function testUserWithBothGroupPermissionsAndMultiPermissionScopeGetsViewEditAndAccessContent(): void {
    $this->createGroupTypeWithRoles();
    $group = Group::create([
      'type' => self::GROUP_TYPE_ID,
      'label' => 'Test Group Multi',
    ]);
    $group->save();

    $user_both = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
    ]);
    $user_both->addRole(self::ROLE_EXAMPLE_OUTSIDER);
    $user_both->save();

    $consumer_user = Consumer::create([
      'client_id' => 'test_multi_user',
      'label' => 'Test Multi User',
      'secret' => 'secret',
      'grant_types' => ['authorization_code'],
      'scopes' => ['test:group:multi_outsider'],
      'user_id' => $user_both->id(),
    ]);
    $consumer_user->save();

    $token_user_both = Oauth2Token::create([
      'bundle' => 'access_token',
      'auth_user_id' => $user_both->id(),
      'client' => $consumer_user,
      'scopes' => ['test:group:multi_outsider'],
      'value' => 'token_user_multi',
      'expire' => \Drupal::time()->getRequestTime() + 3600,
      'status' => TRUE,
    ]);
    $token_user_both->save();

    $token_auth_user_both = new TokenAuthUser($token_user_both);

    $this->assertTrue(
      $this->groupPermissionChecker->hasPermissionInGroup(self::PERMISSION_GROUP_VIEW, $token_auth_user_both, $group),
      'User with both permissions and multi-permission scope should have view group.'
    );
    $this->assertTrue(
      $this->groupPermissionChecker->hasPermissionInGroup(self::PERMISSION_GROUP_EDIT, $token_auth_user_both, $group),
      'User with both permissions and multi-permission scope should have edit group.'
    );
    $this->assertTrue(
      $token_auth_user_both->hasPermission(self::PERMISSION_DRUPAL),
      'User with both permissions and multi-permission scope should have Drupal permission access content.'
    );
  }

  /**
   * Tests user with only view group perm gets view and access content, no edit.
   *
   * Scope grants view group, edit group, and access content. User has only
   * view group and access content. Intersection allows view group and
   * Drupal access content, denies edit group.
   */
  public function testUserWithViewOnlyGroupPermissionAndMultiPermissionScopeGetsViewNotEdit(): void {
    $this->createGroupTypeWithRoles();
    $group = Group::create([
      'type' => self::GROUP_TYPE_ID,
      'label' => 'Test Group Multi',
    ]);
    $group->save();

    $role_view_only = Role::create([
      'id' => self::ROLE_EXAMPLE_OUTSIDER_VIEW_ONLY,
      'label' => 'Example outsider view only',
    ]);
    $role_view_only->grantPermission(self::PERMISSION_DRUPAL);
    $role_view_only->save();

    $outsider_view_only_role = GroupRole::create([
      'id' => self::GROUP_TYPE_ID . '-outsider-view-only',
      'label' => 'Example outsider view only',
      'group_type' => self::GROUP_TYPE_ID,
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => self::ROLE_EXAMPLE_OUTSIDER_VIEW_ONLY,
    ]);
    $outsider_view_only_role->grantPermission(self::PERMISSION_GROUP_VIEW)->save();

    $user_view_only = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
    ]);
    $user_view_only->addRole(self::ROLE_EXAMPLE_OUTSIDER_VIEW_ONLY);
    $user_view_only->save();

    $consumer_view_only = Consumer::create([
      'client_id' => 'test_multi_user_view_only',
      'label' => 'Test Multi User View Only',
      'secret' => 'secret',
      'grant_types' => ['authorization_code'],
      'scopes' => ['test:group:multi_outsider'],
      'user_id' => $user_view_only->id(),
    ]);
    $consumer_view_only->save();

    $token_user_view_only = Oauth2Token::create([
      'bundle' => 'access_token',
      'auth_user_id' => $user_view_only->id(),
      'client' => $consumer_view_only,
      'scopes' => ['test:group:multi_outsider'],
      'value' => 'token_user_view_only',
      'expire' => \Drupal::time()->getRequestTime() + 3600,
      'status' => TRUE,
    ]);
    $token_user_view_only->save();

    $token_auth_user_view_only = new TokenAuthUser($token_user_view_only);

    $this->assertTrue(
      $this->groupPermissionChecker->hasPermissionInGroup(self::PERMISSION_GROUP_VIEW, $token_auth_user_view_only, $group),
      'User with only view group and multi-permission scope should have view group (intersection).'
    );
    $this->assertFalse(
      $this->groupPermissionChecker->hasPermissionInGroup(self::PERMISSION_GROUP_EDIT, $token_auth_user_view_only, $group),
      'User with only view group and multi-permission scope should not have edit group (intersection).'
    );
    $this->assertTrue(
      $token_auth_user_view_only->hasPermission(self::PERMISSION_DRUPAL),
      'User with multi-permission scope should have Drupal permission access content (from scope).'
    );
  }

  /**
   * Tests that a user with only a global Drupal role has access content.
   *
   * User does not have group role.
   */
  public function testUserWithGlobalRoleOnlyHasDrupalPermission(): void {
    $this->createGroupTypeWithRoles();
    $group = Group::create([
      'type' => self::GROUP_TYPE_ID,
      'label' => 'Test Group Multi',
    ]);
    $group->save();

    $role_content_access = Role::create([
      'id' => self::ROLE_CONTENT_ACCESS_ONLY,
      'label' => 'Content access only',
    ]);
    $role_content_access->grantPermission(self::PERMISSION_DRUPAL);
    $role_content_access->save();

    $user_content_access = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
    ]);
    $user_content_access->addRole(self::ROLE_CONTENT_ACCESS_ONLY);
    $user_content_access->save();

    $consumer_content_access = Consumer::create([
      'client_id' => 'test_multi_user_content_access',
      'label' => 'Test Multi User Content Access',
      'secret' => 'secret',
      'grant_types' => ['authorization_code'],
      'scopes' => ['test:group:multi_outsider'],
      'user_id' => $user_content_access->id(),
    ]);
    $consumer_content_access->save();

    $token_user_content_access = Oauth2Token::create([
      'bundle' => 'access_token',
      'auth_user_id' => $user_content_access->id(),
      'client' => $consumer_content_access,
      'scopes' => ['test:group:multi_outsider'],
      'value' => 'token_user_content_access',
      'expire' => \Drupal::time()->getRequestTime() + 3600,
      'status' => TRUE,
    ]);
    $token_user_content_access->save();

    $token_auth_user_content_access = new TokenAuthUser($token_user_content_access);

    $this->assertTrue(
      $token_auth_user_content_access->hasPermission(self::PERMISSION_DRUPAL),
      'User with global role granting access content (no group role) should have Drupal permission access content.'
    );
    $this->assertFalse(
      $token_auth_user_content_access->hasPermission(self::PERMISSION_GROUP_VIEW),
      'User with only global role (no group role) should not have view group permission.'
    );
    $this->assertFalse(
      $token_auth_user_content_access->hasPermission(self::PERMISSION_GROUP_EDIT),
      'User with only global role (no group role) should not have edit group permission.'
    );
  }

}

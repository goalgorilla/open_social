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
use Drupal\simple_oauth_static_scope\Plugin\Oauth2Scope;
use Drupal\Tests\group\Kernel\GroupKernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests GroupPermissionChecker with OAuth scopes.
 *
 * Verifies that the decorated checker grants only the group permissions
 * listed in the token's scopes, and does not grant permissions not in the
 * scope.
 *
 * @group group
 */
class GroupPermissionCheckerOAuthScopeTest extends GroupKernelTestBase {

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
    'social_group_test_scope',
  ];

  /**
   * The group permission checker service.
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

    // Enable static scope provider.
    $this->config('simple_oauth.settings')
      ->set('scope_provider', 'static')
      ->save();

    $this->groupPermissionChecker = $this->container->get('group_permission.checker');
  }

  /**
   * Tests that the checker grants only group permissions listed in token scope.
   *
   * The scope test:group:permission grants only 'view group' (and Drupal
   * 'access content') for flexible_group. The decorated checker must
   * grant that group permission but must not grant other group permissions
   * that are not in the scope.
   */
  public function testOauthScopeGrantsOnlyListedGroupPermissions(): void {
    $user = User::create([
      'name' => 'test_user',
      'mail' => 'test@example.com',
    ]);
    $user->save();

    $group_type = GroupType::create([
      'id' => 'flexible_group',
      'label' => 'Flexible Group Type',
      'creator_wizard' => FALSE,
    ]);
    $group_type->save();

    // Grant the user more group permissions via an outsider group role than the
    // token scope allows. The scope test:group:permission grants only
    // 'view group'. Without the decorator, the checker would grant both
    // permissions (role-based); with the decorator, only the permission in the
    // scope is granted.
    $global_role_id = 'test_flexible_outsider_view_edit';
    $role = Role::create([
      'id' => $global_role_id,
      'label' => 'Flexible outsider view and edit',
    ]);
    $role->save();

    $outsider_group_role = GroupRole::create([
      'id' => 'flexible_group-outsider-view-edit',
      'label' => 'Flexible outsider view and edit',
      'group_type' => 'flexible_group',
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => $global_role_id,
    ]);
    $outsider_group_role
      ->grantPermission('view group')
      ->grantPermission('edit group')
      ->save();

    $user->addRole($global_role_id);
    $user->save();

    $group = Group::create([
      'type' => 'flexible_group',
      'label' => 'Test Group',
    ]);
    $group->save();

    /** @var \Drupal\simple_oauth\Oauth2ScopeProviderInterface $scope_provider */
    $scope_provider = $this->container->get('simple_oauth.oauth2_scope.provider');
    $scope = $scope_provider->loadByName('test:group:permission');
    $this->assertInstanceOf(Oauth2Scope::class, $scope);

    $consumer = Consumer::create([
      'client_id' => 'test_client',
      'label' => 'Test Client',
      'secret' => 'test_secret',
      'grant_types' => ['client_credentials'],
      'scopes' => [$scope->id()],
    ]);
    $consumer->save();

    $token = Oauth2Token::create([
      'bundle' => 'access_token',
      'auth_user_id' => $user->id(),
      'client' => $consumer,
      'scopes' => [$scope->id()],
      'value' => 'test_token_value',
      'expire' => \Drupal::time()->getRequestTime() + 3600,
      'status' => TRUE,
    ]);
    $token->save();

    $token_auth_user = new TokenAuthUser($token);

    // Scope test:group:permission grants 'view group'
    // for flexible_group in outsider context. So this must be allowed.
    $this->assertTrue(
      $this->groupPermissionChecker->hasPermissionInGroup(
        'view group',
        $token_auth_user,
        $group
      ),
      'The checker must grant the group permission that is listed in the token scope.'
    );

    // User has 'edit group' via role but scope does not grant it. The decorator
    // must restrict to scope, so the token must not have this permission.
    $this->assertFalse(
      $this->groupPermissionChecker->hasPermissionInGroup(
        'edit group',
        $token_auth_user,
        $group
      ),
      'The checker must not grant group permissions that are not listed in the token scope.'
    );
  }

}

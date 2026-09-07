<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group\Unit;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface;
use Drupal\social_group\GroupRelationshipStanding;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the single owner of the group relationship standing rule.
 *
 * This class is the rule both enforcement points ask - postSave() on the
 * computed groups field, and SetGroupsForNodeService for the writes that never
 * reach an entity save - so every branch is pinned here. That the rule then
 * actually protects a relationship is proven against real groups, nodes and
 * relationships in the two kernel tests:
 * \Drupal\Tests\social_group\Kernel\SocialGroupReferenceItemListPostSaveTest
 * and \Drupal\Tests\social_group\Kernel\SetGroupsForNodeServiceStandingTest.
 *
 * @coversDefaultClass \Drupal\social_group\GroupRelationshipStanding
 *
 * @group social_group
 */
class GroupRelationshipStandingTest extends UnitTestCase {

  /**
   * A write with no user context behind it is never restricted.
   *
   * Cron, queue workers, Drush commands and migrations all run as the anonymous
   * session. Reading that as "standing on nothing" would make every group
   * relationship on the site immutable to automation.
   *
   * @covers ::lockedGroupIds
   */
  public function testAnonymousWriteIsNeverRestricted(): void {
    $standing = $this->standing(
      $this->account(TRUE),
      'node',
      NULL,
      'create group_node:article entity',
    );

    $this->assertSame(
      [],
      $standing->lockedGroupIds(
        $this->entity('node'),
        ['1' => $this->relationship('group_node:article', FALSE, FALSE)],
      ),
      'a write without a user behind it must not be frozen',
    );
  }

  /**
   * A holder of "manage all groups" keeps full control.
   *
   * @covers ::lockedGroupIds
   */
  public function testManageAllGroupsGrantsFullControl(): void {
    $account = $this->account(FALSE, [
      ['manage all groups', TRUE],
      ['administer nodes', FALSE],
    ]);

    $standing = $this->standing($account, 'node', NULL, 'create group_node:article entity');

    $this->assertSame(
      [],
      $standing->lockedGroupIds(
        $this->entity('node'),
        ['1' => $this->relationship('group_node:article', FALSE, FALSE)],
      ),
      'a site-wide group administrator is not restricted by group standing',
    );
  }

  /**
   * A node is judged against "administer nodes", not the entity definition.
   *
   * Core's Node entity type declares no admin_permission, so the special case
   * is required rather than redundant.
   *
   * @covers ::lockedGroupIds
   */
  public function testNodeAdminPermissionGrantsFullControl(): void {
    $account = $this->account(FALSE, [
      ['manage all groups', FALSE],
      ['administer nodes', TRUE],
    ]);

    $standing = $this->standing($account, 'node', NULL, 'create group_node:article entity');

    $this->assertSame(
      [],
      $standing->lockedGroupIds(
        $this->entity('node'),
        ['1' => $this->relationship('group_node:article', FALSE, FALSE)],
      ),
      'a content administrator is not restricted by group standing',
    );
  }

  /**
   * A non-node entity type keeps its own administrative permission.
   *
   * @covers ::lockedGroupIds
   */
  public function testNonNodeAdminPermissionGrantsFullControl(): void {
    $account = $this->account(FALSE, [
      ['manage all groups', FALSE],
      ['administer media', TRUE],
    ]);

    $standing = $this->standing($account, 'media', 'administer media', 'create group_media:image entity');

    $this->assertSame(
      [],
      $standing->lockedGroupIds(
        $this->entity('media'),
        ['1' => $this->relationship('group_media:image', FALSE, FALSE)],
      ),
      'a media administrator is not restricted by group standing',
    );
  }

  /**
   * A non-node entity type without an administrative permission is guarded.
   *
   * @covers ::lockedGroupIds
   */
  public function testNonNodeWithoutAdminPermissionIsGuarded(): void {
    $standing = $this->standing(
      $this->account(FALSE),
      'media',
      FALSE,
      'create group_media:image entity',
    );

    $this->assertSame(
      [1 => 1],
      $standing->lockedGroupIds(
        $this->entity('media'),
        ['1' => $this->relationship('group_media:image', FALSE)],
      ),
      'without standing over the group the relationship must be protected',
    );
  }

  /**
   * An entity type with no definition at all is still judged.
   *
   * GetDefinition() is called without exceptions, so an entity type that has
   * been uninstalled returns NULL. That must fall through to the per-group
   * check rather than blow up or grant blanket control.
   *
   * @covers ::lockedGroupIds
   */
  public function testUnknownEntityTypeFallsThroughToTheGroupCheck(): void {
    $standing = $this->standing(
      $this->account(FALSE),
      'gone',
      NULL,
      'create group_gone:thing entity',
    );

    $this->assertSame(
      [1 => 1],
      $standing->lockedGroupIds(
        $this->entity('gone'),
        ['1' => $this->relationship('group_gone:thing', FALSE)],
      ),
      'a missing entity type definition must not grant full control',
    );
  }

  /**
   * A group the account may post in is not locked.
   *
   * Positive control: without it an implementation locking everything would
   * pass every protective case in this class.
   *
   * @covers ::lockedGroupIds
   */
  public function testAccountWithStandingIsNotLocked(): void {
    $standing = $this->standing(
      $this->account(FALSE),
      'node',
      NULL,
      'create group_node:article entity',
    );

    $this->assertSame(
      [],
      $standing->lockedGroupIds(
        $this->entity('node'),
        ['1' => $this->relationship('group_node:article', TRUE)],
      ),
      'an account allowed to post in the group keeps the right to detach it',
    );
  }

  /**
   * A relation granting no entity create permission is left alone.
   *
   * @covers ::lockedGroupIds
   */
  public function testRelationWithoutEntityCreatePermissionIsNotGuarded(): void {
    $standing = $this->standing($this->account(FALSE), 'user', NULL, FALSE);

    $this->assertSame(
      [],
      $standing->lockedGroupIds(
        $this->entity('user'),
        ['1' => $this->relationship('group_membership', FALSE, FALSE)],
      ),
      'group_membership has no entity create permission, so it has no standing rule',
    );
  }

  /**
   * A relationship left by an uninstalled relation plugin is not guarded.
   *
   * @covers ::lockedGroupIds
   */
  public function testRelationWithoutPluginDefinitionIsNotGuarded(): void {
    $standing = $this->standing(
      $this->account(FALSE),
      'media',
      NULL,
      'create group_media:image entity',
      FALSE,
    );

    $this->assertSame(
      [],
      $standing->lockedGroupIds(
        $this->entity('media'),
        ['1' => $this->relationship('group_media:gone', FALSE, FALSE)],
      ),
      'an uninstalled relation plugin must not make writing the entity fatal',
    );
  }

  /**
   * Each group of a cross-posted entity is judged on its own.
   *
   * @covers ::lockedGroupIds
   */
  public function testGroupsAreJudgedIndividually(): void {
    $standing = $this->standing(
      $this->account(FALSE),
      'node',
      NULL,
      'create group_node:article entity',
    );

    $this->assertSame(
      [2 => 2],
      $standing->lockedGroupIds($this->entity('node'), [
        '1' => $this->relationship('group_node:article', TRUE),
        '2' => $this->relationship('group_node:article', FALSE),
      ]),
      'only the group the account cannot post in is locked',
    );
  }

  /**
   * Builds the service under test.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The acting account.
   * @param string $entity_type_id
   *   The entity type the relationships belong to.
   * @param string|false|null $admin_permission
   *   The administrative permission of that entity type, FALSE when it declares
   *   none, or NULL when the entity type has no definition at all.
   * @param string|false $create_permission
   *   The entity create permission the relation grants, or FALSE when it grants
   *   none.
   * @param bool $has_definition
   *   Whether the relation plugin is still installed.
   *
   * @return \Drupal\social_group\GroupRelationshipStanding
   *   The service under test.
   */
  protected function standing(AccountInterface $account, string $entity_type_id, string|false|null $admin_permission, string|false $create_permission, bool $has_definition = TRUE): GroupRelationshipStanding {
    $definition = NULL;

    if ($admin_permission !== NULL) {
      $definition = $this->createMock(EntityTypeInterface::class);
      $definition->method('getAdminPermission')->willReturn($admin_permission);
    }

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getDefinition')
      ->with($entity_type_id, FALSE)
      ->willReturn($definition);

    $provider = $this->createMock(PermissionProviderInterface::class);
    $provider->method('getPermission')
      ->with('create', 'entity')
      ->willReturn($create_permission);

    $relation_type_manager = $this->createMock(GroupRelationTypeManagerInterface::class);
    $relation_type_manager->method('hasDefinition')->willReturn($has_definition);
    $relation_type_manager->method('getPermissionProvider')->willReturn($provider);

    return new GroupRelationshipStanding($entity_type_manager, $relation_type_manager, $account);
  }

  /**
   * Builds the acting account.
   *
   * @param bool $is_anonymous
   *   Whether the write runs without a user context.
   * @param array<int, array<int, string|bool>> $permissions
   *   A hasPermission() return map. Defaults to holding no permission at all.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The account.
   */
  protected function account(bool $is_anonymous, array $permissions = []): AccountInterface {
    $account = $this->createMock(AccountInterface::class);
    $account->method('isAnonymous')->willReturn($is_anonymous);

    if ($permissions === []) {
      $account->method('hasPermission')->willReturn(FALSE);
    }
    else {
      $account->method('hasPermission')->willReturnMap($permissions);
    }

    return $account;
  }

  /**
   * Builds the entity whose relationships are judged.
   *
   * @param string $entity_type_id
   *   The entity type id.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity.
   */
  protected function entity(string $entity_type_id): EntityInterface {
    $entity = $this->createMock(EntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn($entity_type_id);

    return $entity;
  }

  /**
   * Builds a group relationship whose group answers the permission check.
   *
   * @param string $plugin_id
   *   The relation plugin id.
   * @param bool $has_permission
   *   Whether the acting account may create this content in the group.
   * @param bool $expects_permission_check
   *   Whether the group is expected to be asked at all.
   *
   * @return \Drupal\group\Entity\GroupRelationshipInterface
   *   The relationship.
   */
  protected function relationship(string $plugin_id, bool $has_permission, bool $expects_permission_check = TRUE): GroupRelationshipInterface {
    $group = $this->createMock(GroupInterface::class);
    $group->expects($expects_permission_check ? $this->once() : $this->never())
      ->method('hasPermission')
      ->willReturn($has_permission);

    $relationship = $this->createMock(GroupRelationshipInterface::class);
    $relationship->method('getPluginId')->willReturn($plugin_id);
    $relationship->method('getGroup')->willReturn($group);

    return $relationship;
  }

}

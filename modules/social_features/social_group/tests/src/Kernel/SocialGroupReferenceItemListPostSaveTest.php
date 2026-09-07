<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group\Kernel;

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupRelationship;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\Tests\group\Kernel\GroupKernelTestBase;

/**
 * Tests the standing guard in SocialGroupReferenceItemList::postSave().
 *
 * A group relationship the acting account has no standing over is not that
 * account's to remove, whatever the saved field value says. The guard lives in
 * postSave() rather than in the group selector widget so that it covers every
 * write path that goes through an entity save - the node form, JSON:API,
 * migrations and programmatic saves.
 *
 * The writes that never reach an entity save - SetGroupsForNodeService, called
 * directly by the GraphQL updateTopic and updateEvent mutations - are covered
 * by \Drupal\Tests\social_group\Kernel\SetGroupsForNodeServiceStandingTest.
 * Both enforcement points ask the same rule of the same owner,
 * \Drupal\social_group\GroupRelationshipStanding.
 *
 * Every assertion here reads the relationships back from storage, and each
 * protective case is paired with a positive control that still deletes, so a
 * "postSave never deletes" implementation cannot pass this class.
 *
 * @coversDefaultClass \Drupal\social_group\Plugin\Field\SocialGroupReferenceItemList
 *
 * @group social_group
 */
class SocialGroupReferenceItemListPostSaveTest extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity',
    'flexible_permissions',
    'group',
    'gnode',
    'node',
    'options',
    'flag',
    'social_group',
  ];

  /**
   * The group the node lives in.
   */
  protected GroupInterface $group;

  /**
   * A second group, for the cross-posting case.
   */
  protected GroupInterface $otherGroup;

  /**
   * The group content relation plugin id used throughout this test.
   */
  protected const PLUGIN_ID = 'group_node:article';

  /**
   * The group permission that grants standing over a group.
   */
  protected const CREATE_PERMISSION = 'create group_node:article entity';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('flagging');
    $this->installSchema('flag', ['flag_counts']);

    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();

    $group_type = $this->createGroupType(['id' => 'example']);

    /** @var \Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface $relationship_type_storage */
    $relationship_type_storage = $this->entityTypeManager()->getStorage('group_content_type');
    $relationship_type_storage->createFromPlugin($group_type, self::PLUGIN_ID)->save();

    // Members may post articles in the group, which is what gives them
    // standing over it. Outsiders may not, which is the situation the guard
    // protects: the ex-author of PROD-36392 who left the group.
    $this->createGroupRole([
      'id' => 'example-insider',
      'group_type' => 'example',
      'scope' => 'insider',
      'global_role' => 'authenticated',
      'permissions' => [self::CREATE_PERMISSION],
    ]);
    $this->createGroupRole([
      'id' => 'example-outsider',
      'group_type' => 'example',
      'scope' => 'outsider',
      'global_role' => 'authenticated',
      'permissions' => [],
    ]);

    $this->group = $this->createGroup(['type' => 'example', 'label' => 'Group A']);
    $this->otherGroup = $this->createGroup(['type' => 'example', 'label' => 'Group B']);
  }

  /**
   * An account with no standing over the group cannot drop the relationship.
   *
   * This is the PROD-36392 data loss: the widget offered the editor an empty
   * group value, and saving it deleted the relationship.
   *
   * @covers ::postSave
   * @covers \Drupal\social_group\GroupRelationshipStanding::lockedGroupIds
   */
  public function testAccountWithoutStandingCannotDropRelationship(): void {
    $node = $this->createNodeInGroups([$this->group]);
    $outsider = $this->createUser();

    $this->assertFalse(
      $this->group->hasPermission(self::CREATE_PERMISSION, $outsider),
      'precondition: the editor has no standing over the group',
    );

    $this->setCurrentUser($outsider);
    $this->saveWithGroups($node, []);

    $this->assertEqualsCanonicalizing(
      [$this->group->id()],
      $this->relationshipGroupIds($node),
      'a group the editor was never able to act on must survive the save',
    );
  }

  /**
   * The preserved group is reflected in the computed field after the save.
   *
   * Social_group_save_group_from_node() reconciles the very same relationships
   * once more from $node->get('groups') right after the entity save, so a field
   * value left out of step with storage would delete what postSave() just kept.
   *
   * @covers ::postSave
   */
  public function testPreservedGroupIsReflectedInTheFieldValue(): void {
    $node = $this->createNodeInGroups([$this->group]);

    $this->setCurrentUser($this->createUser());
    $this->saveWithGroups($node, []);

    $this->assertEqualsCanonicalizing(
      [$this->group->id()],
      array_column($node->get('groups')->getValue(), 'target_id'),
      'the computed value must name the group that was actually kept',
    );
  }

  /**
   * A member who may post in the group can still remove it deliberately.
   *
   * Positive control: without it, an implementation that simply never deletes
   * would pass every protective case above.
   *
   * @covers ::postSave
   * @covers \Drupal\social_group\GroupRelationshipStanding::lockedGroupIds
   */
  public function testAccountWithStandingCanDropRelationship(): void {
    $node = $this->createNodeInGroups([$this->group]);
    $member = $this->createUser();
    $this->group->addMember($member);

    $this->assertTrue(
      $this->group->hasPermission(self::CREATE_PERMISSION, $member),
      'precondition: the editor has standing over the group',
    );

    $this->setCurrentUser($member);
    $this->saveWithGroups($node, []);

    $this->assertSame(
      [],
      $this->relationshipGroupIds($node),
      'an explicit removal by an authorised member must still take effect',
    );
  }

  /**
   * A holder of "manage all groups" keeps full control.
   *
   * @covers \Drupal\social_group\GroupRelationshipStanding::lockedGroupIds
   */
  public function testManageAllGroupsCanDropRelationship(): void {
    $node = $this->createNodeInGroups([$this->group]);

    $this->setCurrentUser($this->createUser(['manage all groups']));
    $this->saveWithGroups($node, []);

    $this->assertSame(
      [],
      $this->relationshipGroupIds($node),
      'a site-wide group administrator is not restricted by group standing',
    );
  }

  /**
   * A holder of "administer nodes" keeps full control.
   *
   * @covers \Drupal\social_group\GroupRelationshipStanding::lockedGroupIds
   */
  public function testAdministerNodesCanDropRelationship(): void {
    $node = $this->createNodeInGroups([$this->group]);

    $this->setCurrentUser($this->createUser(['administer nodes']));
    $this->saveWithGroups($node, []);

    $this->assertSame(
      [],
      $this->relationshipGroupIds($node),
      'a content administrator is not restricted by group standing',
    );
  }

  /**
   * A save with no user context keeps the unrestricted behaviour.
   *
   * Cron, queue workers, Drush commands and migrations all run as the anonymous
   * session. Reading that as "standing on nothing" would make every group
   * relationship on the site immutable to automation.
   *
   * @covers \Drupal\social_group\GroupRelationshipStanding::lockedGroupIds
   */
  public function testSaveWithoutUserContextCanDropRelationship(): void {
    $node = $this->createNodeInGroups([$this->group]);

    $this->setCurrentUser(new AnonymousUserSession());
    $this->saveWithGroups($node, []);

    $this->assertSame(
      [],
      $this->relationshipGroupIds($node),
      'a programmatic save without a user behind it must not be frozen',
    );
  }

  /**
   * Cross-posted groups are judged one by one.
   *
   * @covers ::postSave
   * @covers \Drupal\social_group\GroupRelationshipStanding::lockedGroupIds
   */
  public function testCrossPostedGroupsAreJudgedIndividually(): void {
    $node = $this->createNodeInGroups([$this->group, $this->otherGroup]);
    $editor = $this->createUser();
    $this->group->addMember($editor);

    $this->setCurrentUser($editor);
    $this->saveWithGroups($node, []);

    $this->assertEqualsCanonicalizing(
      [$this->otherGroup->id()],
      $this->relationshipGroupIds($node),
      'the group the editor is a member of goes, the other one stays',
    );
  }

  /**
   * The guard never blocks adding a group.
   *
   * @covers ::postSave
   */
  public function testAccountWithoutStandingStillAddsRelationships(): void {
    $node = $this->createNodeInGroups([$this->group]);

    $this->setCurrentUser($this->createUser());
    $this->saveWithGroups($node, [$this->group, $this->otherGroup]);

    $this->assertEqualsCanonicalizing(
      [$this->group->id(), $this->otherGroup->id()],
      $this->relationshipGroupIds($node),
      'the guard only protects existing relationships, it never blocks new ones',
    );
  }

  /**
   * Creates a published article and relates it to the given groups.
   *
   * @param \Drupal\group\Entity\GroupInterface[] $groups
   *   The groups the article must belong to.
   *
   * @return \Drupal\node\NodeInterface
   *   The saved article.
   */
  protected function createNodeInGroups(array $groups): NodeInterface {
    $node = Node::create([
      'type' => 'article',
      'title' => $this->randomMachineName(),
      'status' => 1,
    ]);
    $node->save();

    foreach ($groups as $group) {
      $group->addRelationship($node, self::PLUGIN_ID);
    }

    $this->assertCount(
      count($groups),
      $this->relationshipGroupIds($node),
      'precondition: the article is related to the groups under test',
    );

    return $node;
  }

  /**
   * Writes a group value on the node and saves it.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The article to save.
   * @param \Drupal\group\Entity\GroupInterface[] $groups
   *   The groups the submitted value names.
   */
  protected function saveWithGroups(NodeInterface $node, array $groups): void {
    $node->set('groups', array_map(
      static fn (GroupInterface $group): array => ['target_id' => $group->id()],
      $groups,
    ));
    $node->save();
  }

  /**
   * Reads the node's group ids back from the relationship storage.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The article to inspect.
   *
   * @return array<int, string>
   *   The group ids the article is actually related to.
   */
  protected function relationshipGroupIds(NodeInterface $node): array {
    $gids = [];

    foreach (GroupRelationship::loadByEntity($node) as $relationship) {
      $gids[] = (string) $relationship->getGroupId();
    }

    sort($gids);

    return $gids;
  }

}

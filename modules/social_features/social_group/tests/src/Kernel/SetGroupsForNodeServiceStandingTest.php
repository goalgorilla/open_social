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
 * Tests the standing guard in SetGroupsForNodeService::setGroupsForNode().
 *
 * This is the second enforcement point of the rule owned by
 * \Drupal\social_group\GroupRelationshipStanding. It is needed because
 * setGroupsForNode() deletes group_content entities directly, outside any
 * entity save, so the guard in SocialGroupReferenceItemList::postSave() never
 * sees those deletions. The GraphQL updateTopic and updateEvent mutations both
 * call in here - updateTopic before its $node->save(), updateEvent after it -
 * which is why an API caller was unprotected while a form editor was not.
 *
 * The first test uses the exact call shape of the "remove from all groups"
 * mutation branch: setGroupsForNode($node, $current_groups, [],
 * $current_groups) as written in UpdateTopic and UpdateEvent.
 *
 * Every case asserts against relationships read back from storage, and each
 * protective case is paired with a positive control that still removes, so a
 * "setGroupsForNode never removes" implementation cannot pass this class.
 *
 * The positive controls deliberately pass $original_groups identical to
 * $groups_to_add. That is the documented way to reach the removal without
 * entering the notification-cleanup branch of setGroupsForNode(), which queries
 * the activity and message storages and would drag the whole activity_creator
 * dependency tree into this kernel test for no coverage of the guard.
 *
 * @coversDefaultClass \Drupal\social_group\SetGroupsForNodeService
 *
 * @group social_group
 */
class SetGroupsForNodeServiceStandingTest extends GroupKernelTestBase {

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
   * The group the acting account has no standing over.
   */
  protected GroupInterface $lockedGroup;

  /**
   * The group the acting account may act on.
   */
  protected GroupInterface $openGroup;

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
    // protects: the ex-author of PROD-36392 who left the group, and the
    // permissionless machine account behind a client-credentials API call.
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

    $this->lockedGroup = $this->createGroup(['type' => 'example', 'label' => 'Group A']);
    $this->openGroup = $this->createGroup(['type' => 'example', 'label' => 'Group B']);
  }

  /**
   * The "remove from all groups" API branch cannot drop a locked group.
   *
   * This is the GraphQL half of PROD-36392: updateTopic and updateEvent hand
   * setGroupsForNode() every current group as $groups_to_remove, and until now
   * it deleted all of them whoever asked.
   *
   * @covers ::setGroupsForNode
   */
  public function testApiRemoveFromAllGroupsCannotDropLockedGroup(): void {
    $node = $this->createNodeInGroups([$this->lockedGroup]);
    $outsider = $this->createUser();

    $this->assertFalse(
      $this->lockedGroup->hasPermission(self::CREATE_PERMISSION, $outsider),
      'precondition: the caller has no standing over the group',
    );

    $this->setCurrentUser($outsider);
    $current = [$this->lockedGroup->id() => $this->lockedGroup->id()];
    $this->setGroupsForNode($node, $current, [], $current);

    $this->assertEqualsCanonicalizing(
      [$this->lockedGroup->id()],
      $this->relationshipGroupIds($node),
      'a group the caller was never able to act on must survive the API call',
    );
  }

  /**
   * An account with standing can still remove a group.
   *
   * Positive control: without it, an implementation that simply never removes
   * would pass every protective case in this class.
   *
   * @covers ::setGroupsForNode
   */
  public function testAccountWithStandingCanStillRemoveGroup(): void {
    $node = $this->createNodeInGroups([$this->lockedGroup, $this->openGroup]);
    $member = $this->createUser();
    $this->lockedGroup->addMember($member);

    $this->assertTrue(
      $this->lockedGroup->hasPermission(self::CREATE_PERMISSION, $member),
      'precondition: the caller has standing over the group it removes',
    );

    $this->setCurrentUser($member);
    $keep = [$this->openGroup->id() => $this->openGroup->id()];
    $this->setGroupsForNode($node, [$this->lockedGroup->id() => $this->lockedGroup->id()], $keep, $keep);

    $this->assertEqualsCanonicalizing(
      [$this->openGroup->id()],
      $this->relationshipGroupIds($node),
      'a deliberate removal by an authorised member must still take effect',
    );
  }

  /**
   * A holder of "manage all groups" keeps full control.
   *
   * @covers ::setGroupsForNode
   */
  public function testManageAllGroupsCanStillRemoveGroup(): void {
    $node = $this->createNodeInGroups([$this->lockedGroup, $this->openGroup]);

    $this->setCurrentUser($this->createUser(['manage all groups']));
    $keep = [$this->openGroup->id() => $this->openGroup->id()];
    $this->setGroupsForNode($node, [$this->lockedGroup->id() => $this->lockedGroup->id()], $keep, $keep);

    $this->assertEqualsCanonicalizing(
      [$this->openGroup->id()],
      $this->relationshipGroupIds($node),
      'a site-wide group administrator is not restricted by group standing',
    );
  }

  /**
   * A call with no user context keeps the unrestricted behaviour.
   *
   * Cron, queue workers, Drush commands and migrations all run as the anonymous
   * session, and social_album calls into this service as well.
   *
   * @covers ::setGroupsForNode
   */
  public function testCallWithoutUserContextCanStillRemoveGroup(): void {
    $node = $this->createNodeInGroups([$this->lockedGroup, $this->openGroup]);

    $this->setCurrentUser(new AnonymousUserSession());
    $keep = [$this->openGroup->id() => $this->openGroup->id()];
    $this->setGroupsForNode($node, [$this->lockedGroup->id() => $this->lockedGroup->id()], $keep, $keep);

    $this->assertEqualsCanonicalizing(
      [$this->openGroup->id()],
      $this->relationshipGroupIds($node),
      'a programmatic call without a user behind it must not be frozen',
    );
  }

  /**
   * Cross-posted groups are judged one by one, not as a block.
   *
   * The node sits in a group the caller may act on and one it may not, and the
   * call asks for both to go. Only the first may.
   *
   * @covers ::setGroupsForNode
   */
  public function testCrossPostedGroupsAreJudgedIndividually(): void {
    $node = $this->createNodeInGroups([$this->lockedGroup, $this->openGroup]);
    $editor = $this->createUser();
    $this->openGroup->addMember($editor);
    $destination = $this->createGroup(['type' => 'example', 'label' => 'Group C']);

    $this->setCurrentUser($editor);
    $add = [$destination->id() => $destination->id()];
    $this->setGroupsForNode(
      $node,
      [
        $this->lockedGroup->id() => $this->lockedGroup->id(),
        $this->openGroup->id() => $this->openGroup->id(),
      ],
      $add,
      $add,
    );

    $this->assertEqualsCanonicalizing(
      [$this->lockedGroup->id(), $destination->id()],
      $this->relationshipGroupIds($node),
      'the group the caller has standing in goes, the locked one stays',
    );
  }

  /**
   * The guard never blocks adding a group.
   *
   * @covers ::setGroupsForNode
   */
  public function testCallerWithoutStandingStillAddsRelationships(): void {
    $node = $this->createNodeInGroups([$this->lockedGroup]);

    $this->setCurrentUser($this->createUser());
    $add = [$this->openGroup->id() => $this->openGroup->id()];
    $this->setGroupsForNode($node, [], $add, $add);

    $this->assertEqualsCanonicalizing(
      [$this->lockedGroup->id(), $this->openGroup->id()],
      $this->relationshipGroupIds($node),
      'the guard only protects existing relationships, it never blocks new ones',
    );
  }

  /**
   * Calls the service under test.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The article whose groups are reconciled.
   * @param array $groups_to_remove
   *   The group ids the caller asks to detach.
   * @param array $groups_to_add
   *   The group ids the caller asks to attach.
   * @param array $original_groups
   *   The group ids the caller reports as current.
   */
  protected function setGroupsForNode(NodeInterface $node, array $groups_to_remove, array $groups_to_add, array $original_groups): void {
    /** @var \Drupal\social_group\SetGroupsForNodeService $service */
    $service = $this->container->get('social_group.set_groups_for_node_service');
    $service->setGroupsForNode($node, $groups_to_remove, $groups_to_add, $original_groups, FALSE);
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

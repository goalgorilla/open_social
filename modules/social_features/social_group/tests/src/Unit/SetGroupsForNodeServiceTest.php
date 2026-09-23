<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group\Unit;

use Drupal\activity_creator\FirstPublicationMarker;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\Entity\Storage\GroupRelationshipStorageInterface;
use Drupal\node\NodeInterface;
use Drupal\social_group\SetGroupsForNodeService;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for SetGroupsForNodeService.
 *
 * @group social_group
 *
 * @coversDefaultClass \Drupal\social_group\SetGroupsForNodeService
 */
final class SetGroupsForNodeServiceTest extends UnitTestCase {

  /**
   * The entity type manager mock.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * The module handler mock.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private ModuleHandlerInterface&MockObject $moduleHandler;

  /**
   * The activity storage mock.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private EntityStorageInterface&MockObject $activityStorage;

  /**
   * The message storage mock.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private EntityStorageInterface&MockObject $messageStorage;

  /**
   * The group storage mock.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private EntityStorageInterface&MockObject $groupStorage;

  /**
   * The group relationship storage mock.
   *
   * @var \Drupal\group\Entity\Storage\GroupRelationshipStorageInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private GroupRelationshipStorageInterface&MockObject $relationshipStorage;

  /**
   * The activity entity query mock.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private QueryInterface&MockObject $activityQuery;

  /**
   * The node the service acts on.
   *
   * @var \Drupal\node\NodeInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private NodeInterface&MockObject $node;

  /**
   * The service under test.
   */
  private SetGroupsForNodeService $service;

  /**
   * The marker state observed from inside the add loop, one entry per group.
   *
   * @var bool[]
   */
  private array $markerInsideLoop = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->activityQuery = $this->createMock(QueryInterface::class);
    $this->activityQuery->method('accessCheck')->willReturnSelf();
    $this->activityQuery->method('condition')->willReturnSelf();
    $this->activityQuery->method('execute')->willReturn([]);

    $this->activityStorage = $this->createMock(EntityStorageInterface::class);
    $this->activityStorage->method('getQuery')->willReturn($this->activityQuery);

    $this->messageStorage = $this->createMock(EntityStorageInterface::class);
    $this->messageStorage->method('loadByProperties')->willReturn([]);

    $this->groupStorage = $this->createMock(EntityStorageInterface::class);

    $this->relationshipStorage = $this->createMock(GroupRelationshipStorageInterface::class);
    $this->relationshipStorage->method('loadByEntity')->willReturn([]);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->method('getStorage')->willReturnCallback(
      fn (string $entity_type_id): EntityStorageInterface => match ($entity_type_id) {
        'activity' => $this->activityStorage,
        'message' => $this->messageStorage,
        'group' => $this->groupStorage,
        'group_content' => $this->relationshipStorage,
        default => throw new \LogicException('Unexpected storage: ' . $entity_type_id),
      }
    );

    $entity_type_repository = $this->createMock(EntityTypeRepositoryInterface::class);
    $entity_type_repository->method('getEntityTypeFromClass')->willReturn('group');

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('entity_type.repository', $entity_type_repository);
    \Drupal::setContainer($container);

    $this->node = $this->createMock(NodeInterface::class);
    $this->node->method('id')->willReturn(42);
    $this->node->method('bundle')->willReturn('topic');
    $this->node->method('getOwnerId')->willReturn(7);

    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->service = new SetGroupsForNodeService($this->entityTypeManager, $this->moduleHandler);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // The marker is a request scoped static: a value leaking from one test
    // method into the next would make these assertions lie.
    FirstPublicationMarker::setRaised(FALSE);

    parent::tearDown();
  }

  /**
   * Nothing happens, and the marker stays down, when no group changes.
   *
   * @covers ::setGroupsForNode
   */
  public function testNoGroupChangeReturnsEarly(): void {
    $this->entityTypeManager->expects($this->never())->method('getStorage');
    $this->moduleHandler->expects($this->never())->method('invokeAll');

    $this->assertSame($this->node, $this->service->setGroupsForNode($this->node, [], [], [], TRUE));
    $this->assertFalse(FirstPublicationMarker::isRaised());
  }

  /**
   * Creating content raises the marker for the whole add loop.
   *
   * @covers ::setGroupsForNode
   * @covers ::addGroupRelationship
   */
  public function testCreationRaisesTheMarkerAroundTheAddLoop(): void {
    $this->expectGroupsToAdd([1, 2]);
    // The content is new, so hook_social_group_move must not be invoked even
    // though the community-to-group branch marked the content as moved.
    $this->moduleHandler->expects($this->never())->method('invokeAll');

    $this->service->setGroupsForNode($this->node, [], [1, 2], [], TRUE);

    $this->assertSame([TRUE, TRUE], $this->markerInsideLoop);
    $this->assertFalse(FirstPublicationMarker::isRaised());
  }

  /**
   * Adding a group to existing content leaves the marker down.
   *
   * @covers ::setGroupsForNode
   * @covers ::addGroupRelationship
   */
  public function testEditNeverRaisesTheMarker(): void {
    $this->expectGroupsToAdd([1, 2]);
    $this->moduleHandler->expects($this->once())
      ->method('invokeAll')
      ->with('social_group_move', [$this->node]);

    $this->service->setGroupsForNode($this->node, [], [1, 2], [], FALSE);

    $this->assertSame([FALSE, FALSE], $this->markerInsideLoop);
    $this->assertFalse(FirstPublicationMarker::isRaised());
  }

  /**
   * A failure inside the add loop still lowers the marker.
   *
   * @covers ::setGroupsForNode
   */
  public function testMarkerIsLoweredWhenTheAddLoopThrows(): void {
    $group = $this->createMock(Group::class);
    $group->method('id')->willReturn(1);
    $group->method('addRelationship')
      ->willThrowException(new \RuntimeException('Relationship failed.'));
    $this->groupStorage->method('loadMultiple')->willReturn([1 => $group]);

    try {
      $this->service->setGroupsForNode($this->node, [], [1], [], TRUE);
      $this->fail('Expected the storage exception to bubble up.');
    }
    catch (\RuntimeException $e) {
      $this->assertSame('Relationship failed.', $e->getMessage());
    }

    $this->assertFalse(FirstPublicationMarker::isRaised());
  }

  /**
   * A marker raised by an enclosing raise site survives this one.
   *
   * The three raise sites are sequential today, but nothing in the code base
   * enforces that, and a site that lowered the marker for good would silently
   * cut the fan-out of the enclosing site short. The finally block therefore
   * restores the value it replaced instead of lowering unconditionally.
   *
   * @covers ::setGroupsForNode
   */
  public function testTheMarkerRaisedByAnEnclosingSiteIsRestored(): void {
    FirstPublicationMarker::setRaised(TRUE);

    $this->expectGroupsToAdd([1, 2]);
    $this->moduleHandler->expects($this->once())
      ->method('invokeAll')
      ->with('social_group_move', [$this->node]);

    $this->service->setGroupsForNode($this->node, [], [1, 2], [], FALSE);

    // The update path still lowers the marker for its own loop.
    $this->assertSame([FALSE, FALSE], $this->markerInsideLoop);
    // But the enclosing site gets its marker back.
    $this->assertTrue(FirstPublicationMarker::isRaised());
  }

  /**
   * Moving content out of a group deletes its group stream activities.
   *
   * @covers ::setGroupsForNode
   */
  public function testMoveFromGroupDeletesMatchingActivities(): void {
    $this->whenMessageTemplatesExist();
    $this->whenActivitiesMatch([11]);
    // Once for the create_topic_group activities, once for the
    // moved_content_between_groups activities.
    $this->activityStorage->expects($this->exactly(2))->method('delete');

    $this->expectGroupsToAdd([2]);
    $this->moduleHandler->expects($this->once())
      ->method('invokeAll')
      ->with('social_group_move', [$this->node]);

    $this->service->setGroupsForNode($this->node, [], [2], [1], FALSE);
  }

  /**
   * A missing message template leaves the query unrestricted but harmless.
   *
   * @covers ::setGroupsForNode
   */
  public function testMoveFromGroupWithoutMessageTemplate(): void {
    $this->whenActivitiesMatch([11]);
    // The template condition is never added, but the deletion still runs for
    // both the move-out query and the moved-content query.
    $this->activityStorage->expects($this->exactly(2))->method('delete');

    $this->expectGroupsToAdd([2]);

    $this->service->setGroupsForNode($this->node, [], [2], [1], FALSE);
  }

  /**
   * Moving content from the community into a group finds no activities.
   *
   * @covers ::setGroupsForNode
   */
  public function testMoveFromCommunityWithTemplateButNoActivities(): void {
    $this->whenMessageTemplatesExist();
    $this->activityStorage->expects($this->never())->method('delete');

    $this->expectGroupsToAdd([1]);
    $this->moduleHandler->expects($this->once())
      ->method('invokeAll')
      ->with('social_group_move', [$this->node]);

    $this->service->setGroupsForNode($this->node, [], [1], [], FALSE);
  }

  /**
   * An unchanged group list skips the activity clean-up entirely.
   *
   * @covers ::setGroupsForNode
   */
  public function testUnchangedGroupsSkipTheActivityCleanup(): void {
    $this->activityStorage->expects($this->never())->method('getQuery');
    $this->messageStorage->expects($this->never())->method('loadByProperties');
    $this->moduleHandler->expects($this->never())->method('invokeAll');

    $this->expectGroupsToAdd([1]);

    $this->service->setGroupsForNode($this->node, [], [1], [1], FALSE);

    $this->assertSame([FALSE], $this->markerInsideLoop);
  }

  /**
   * Removing the only group never marks the content as moved.
   *
   * @covers ::setGroupsForNode
   * @covers ::removeGroupRelationship
   */
  public function testRemoveOnlyDoesNotMarkTheContentAsMoved(): void {
    $group = $this->createMock(Group::class);
    $group->method('id')->willReturn(1);
    $this->groupStorage->method('loadMultiple')->with([1])->willReturn([1 => $group]);

    $matching = $this->relationshipInGroup(1);
    $matching->expects($this->once())->method('delete');
    $other = $this->relationshipInGroup(2);
    $other->expects($this->never())->method('delete');
    $this->whenNodeIsRelatedTo([$matching, $other]);

    $this->activityStorage->expects($this->never())->method('delete');
    $this->moduleHandler->expects($this->never())->method('invokeAll');

    $this->service->setGroupsForNode($this->node, [1], [], [], FALSE);

    $this->assertFalse(FirstPublicationMarker::isRaised());
  }

  /**
   * Removing a group the node is not in deletes nothing.
   *
   * @covers ::removeGroupRelationship
   */
  public function testRemoveGroupRelationshipWithoutRelationships(): void {
    $group = $this->createMock(Group::class);
    $group->expects($this->never())->method('id');

    $this->whenNodeIsRelatedTo([]);

    SetGroupsForNodeService::removeGroupRelationship($this->node, $group);
  }

  /**
   * Content already in the group is not related to it a second time.
   *
   * @covers ::addGroupRelationship
   */
  public function testAddGroupRelationshipSkipsGroupsTheNodeIsAlreadyIn(): void {
    $group = $this->createMock(Group::class);
    $group->method('id')->willReturn(1);
    $group->expects($this->never())->method('addRelationship');

    $this->whenNodeIsRelatedTo([$this->relationshipInGroup(1)]);

    SetGroupsForNodeService::addGroupRelationship($this->node, $group);
  }

  /**
   * Content in another group is still related to the requested group.
   *
   * @covers ::addGroupRelationship
   */
  public function testAddGroupRelationshipRelatesTheNodeToTheGroup(): void {
    $group = $this->createMock(Group::class);
    $group->method('id')->willReturn(1);
    $group->expects($this->once())
      ->method('addRelationship')
      ->with($this->node, 'group_node:topic', ['uid' => 7]);

    // A relationship to a different group must not stop the new one.
    $this->whenNodeIsRelatedTo([$this->relationshipInGroup(2)]);

    SetGroupsForNodeService::addGroupRelationship($this->node, $group);
  }

  /**
   * Prepares the group storage so that the add loop relates the node.
   *
   * Each group records the marker state observed from inside the loop.
   *
   * @param int[] $ids
   *   The group ids to add the node to.
   */
  private function expectGroupsToAdd(array $ids): void {
    $groups = [];

    foreach ($ids as $id) {
      $group = $this->createMock(Group::class);
      $group->method('id')->willReturn($id);
      $group->expects($this->once())
        ->method('addRelationship')
        ->willReturnCallback(function (): void {
          $this->markerInsideLoop[] = FirstPublicationMarker::isRaised();
        });
      $groups[$id] = $group;
    }

    $this->groupStorage->method('loadMultiple')->with($ids)->willReturn($groups);
  }

  /**
   * Makes the message storage return a template to narrow the query with.
   */
  private function whenMessageTemplatesExist(): void {
    $this->messageStorage = $this->createMock(EntityStorageInterface::class);
    $this->messageStorage->method('loadByProperties')
      ->willReturn([3 => $this->createMock(EntityInterface::class)]);
  }

  /**
   * Makes the activity query return the given activity ids.
   *
   * @param int[] $ids
   *   The activity ids the query resolves to.
   */
  private function whenActivitiesMatch(array $ids): void {
    $this->activityQuery = $this->createMock(QueryInterface::class);
    $this->activityQuery->method('accessCheck')->willReturnSelf();
    $this->activityQuery->method('condition')->willReturnSelf();
    $this->activityQuery->method('execute')->willReturn($ids);

    $this->activityStorage = $this->createMock(EntityStorageInterface::class);
    $this->activityStorage->method('getQuery')->willReturn($this->activityQuery);
    $this->activityStorage->method('loadMultiple')->with($ids)->willReturn([]);
  }

  /**
   * Makes the relationship storage report the node's existing relationships.
   *
   * @param \Drupal\group\Entity\GroupRelationshipInterface[] $relationships
   *   The relationships the node already has.
   */
  private function whenNodeIsRelatedTo(array $relationships): void {
    $this->relationshipStorage = $this->createMock(GroupRelationshipStorageInterface::class);
    $this->relationshipStorage->method('loadByEntity')->willReturn($relationships);
  }

  /**
   * Builds a relationship mock pointing at the group with the given id.
   *
   * @param int $group_id
   *   The id of the group the relationship belongs to.
   *
   * @return \Drupal\group\Entity\GroupRelationshipInterface&\PHPUnit\Framework\MockObject\MockObject
   *   The relationship mock.
   */
  private function relationshipInGroup(int $group_id): GroupRelationshipInterface&MockObject {
    $group = $this->createMock(Group::class);
    $group->method('id')->willReturn($group_id);

    $relationship = $this->createMock(GroupRelationshipInterface::class);
    $relationship->method('getGroup')->willReturn($group);

    return $relationship;
  }

}

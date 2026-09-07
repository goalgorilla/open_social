<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group\Unit\Plugin\Field;

use Drupal\activity_creator\FirstPublicationMarker;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupRelationship;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\Entity\Storage\GroupRelationshipStorageInterface;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;
use Drupal\social_group\GroupRelationshipStanding;
use Drupal\social_group\Plugin\Field\SocialGroupReferenceItemList;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for SocialGroupReferenceItemList.
 *
 * @group social_group
 *
 * @coversDefaultClass \Drupal\social_group\Plugin\Field\SocialGroupReferenceItemList
 */
final class SocialGroupReferenceItemListTest extends UnitTestCase {

  /**
   * The entity type manager mock.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * The group storage mock, answering Group::load().
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private EntityStorageInterface&MockObject $groupStorage;

  /**
   * The group type storage mock.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private EntityStorageInterface&MockObject $groupTypeStorage;

  /**
   * The group relationship storage mock, answering GroupRelationship::create().
   *
   * @var \Drupal\group\Entity\Storage\GroupRelationshipStorageInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private GroupRelationshipStorageInterface&MockObject $relationshipStorage;

  /**
   * The group relationship type storage mock.
   *
   * @var \Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private GroupRelationshipTypeStorageInterface&MockObject $relationshipTypeStorage;

  /**
   * The field type plugin manager mock, answering createItem().
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private FieldTypePluginManagerInterface&MockObject $fieldTypeManager;

  /**
   * The marker state observed from inside the creation loop, one per group.
   *
   * @var bool[]
   */
  private array $markerInsideLoop = [];

  /**
   * The marker state observed from inside the deletion loop, one per group.
   *
   * @var bool[]
   */
  private array $markerInsideDeleteLoop = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->groupStorage = $this->createMock(EntityStorageInterface::class);
    $this->groupTypeStorage = $this->createMock(EntityStorageInterface::class);
    $this->relationshipStorage = $this->createMock(GroupRelationshipStorageInterface::class);
    $this->relationshipStorage->method('loadByEntity')->willReturn([]);
    $this->relationshipTypeStorage = $this->createMock(GroupRelationshipTypeStorageInterface::class);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->method('getStorage')->willReturnCallback(
      fn (string $entity_type_id): EntityStorageInterface => match ($entity_type_id) {
        'group' => $this->groupStorage,
        'group_type' => $this->groupTypeStorage,
        'group_content' => $this->relationshipStorage,
        'group_content_type' => $this->relationshipTypeStorage,
        default => throw new \LogicException('Unexpected storage: ' . $entity_type_id),
      }
    );

    // Group::load() and GroupRelationship::create() are static entity calls:
    // they resolve their storage through the entity type repository.
    $entity_type_repository = $this->createMock(EntityTypeRepositoryInterface::class);
    $entity_type_repository->method('getEntityTypeFromClass')->willReturnMap([
      [Group::class, 'group'],
      [GroupRelationship::class, 'group_content'],
    ]);

    $this->fieldTypeManager = $this->createMock(FieldTypePluginManagerInterface::class);

    // Locked groups are covered by SocialGroupReferenceItemListPostSaveTest;
    // here no group is locked, so the submitted value is reconciled as is.
    $group_relationship_standing = $this->createMock(GroupRelationshipStanding::class);
    $group_relationship_standing->method('lockedGroupIds')->willReturn([]);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('entity_type.repository', $entity_type_repository);
    $container->set('plugin.manager.field.field_type', $this->fieldTypeManager);
    $container->set('social_group.group_relationship_standing', $group_relationship_standing);
    \Drupal::setContainer($container);
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
   * A field nobody read or wrote is left entirely alone.
   *
   * @covers ::postSave
   */
  public function testUncomputedFieldReturnsEarly(): void {
    $this->entityTypeManager->expects($this->never())->method('getStorage');

    $list = $this->itemList($this->node());

    $this->assertFalse($list->postSave(FALSE));
    $this->assertFalse(FirstPublicationMarker::isRaised());
  }

  /**
   * An entity saved without any group creates and deletes nothing.
   *
   * @covers ::postSave
   */
  public function testEmptyFieldAndNoRelationshipsChangeNothing(): void {
    $this->relationshipStorage->expects($this->never())->method('create');

    $list = $this->computedList($this->node(), []);

    $this->assertFalse($list->postSave(FALSE));
  }

  /**
   * Creating content raises the marker around every relationship save.
   *
   * @covers ::postSave
   */
  public function testCreationRaisesTheMarkerForEveryRelationship(): void {
    $this->whenGroupsExist([2, 3]);
    $this->whenRelationshipTypeResolves();
    $this->relationshipStorage->expects($this->exactly(2))
      ->method('create')
      ->willReturnCallback(fn (): GroupRelationshipInterface => $this->savableRelationship());

    $list = $this->computedList($this->node(), [2, 3]);

    $this->assertFalse($list->postSave(FALSE));
    $this->assertSame([TRUE, TRUE], $this->markerInsideLoop);
    $this->assertFalse(FirstPublicationMarker::isRaised());
  }

  /**
   * Adding a group to existing content leaves the marker down.
   *
   * This is the acceptance criterion "notifications are only sent when the
   * content is created, not when it is updated": the very same loop runs, and
   * only the $update argument tells the two apart.
   *
   * @covers ::postSave
   */
  public function testEditingNeverRaisesTheMarker(): void {
    $this->whenGroupsExist([2]);
    $this->whenRelationshipTypeResolves();
    $this->relationshipStorage->expects($this->once())
      ->method('create')
      ->willReturnCallback(fn (): GroupRelationshipInterface => $this->savableRelationship());

    $list = $this->computedList($this->node(), [2]);

    $this->assertFalse($list->postSave(TRUE));
    $this->assertSame([FALSE], $this->markerInsideLoop);
    $this->assertFalse(FirstPublicationMarker::isRaised());
  }

  /**
   * A group that cannot be loaded is skipped without stopping the loop.
   *
   * @covers ::postSave
   */
  public function testUnloadableGroupIsSkipped(): void {
    $this->groupStorage->method('load')->willReturnMap([
      [2, NULL],
      [3, $this->group(3)],
    ]);
    $this->whenRelationshipTypeResolves();
    $this->relationshipStorage->expects($this->once())
      ->method('create')
      ->with([
        'type' => 'flexible_group-group_node-topic',
        'gid' => 3,
        'entity_id' => 42,
      ])
      ->willReturnCallback(fn (): GroupRelationshipInterface => $this->savableRelationship());

    $list = $this->computedList($this->node(), [2, 3]);

    $this->assertFalse($list->postSave(FALSE));
    $this->assertSame([TRUE], $this->markerInsideLoop);
  }

  /**
   * A relationship type without a bundle is preferred when it exists.
   *
   * @covers ::postSave
   */
  public function testNonBundledRelationshipTypeIsUsedWhenItLoads(): void {
    $this->whenGroupsExist([2]);
    $this->relationshipTypeStorage->method('getRelationshipTypeId')->willReturnMap([
      ['flexible_group', 'group_node', 'flexible_group-group_node'],
      ['flexible_group', 'group_node:topic', 'flexible_group-group_node-topic'],
    ]);
    $this->relationshipTypeStorage->method('load')->willReturnMap([
      ['flexible_group-group_node', $this->relationshipType('flexible_group-group_node')],
    ]);

    $this->relationshipStorage->expects($this->once())
      ->method('create')
      ->with([
        'type' => 'flexible_group-group_node',
        'gid' => 2,
        'entity_id' => 42,
      ])
      ->willReturnCallback(fn (): GroupRelationshipInterface => $this->savableRelationship());

    $list = $this->computedList($this->node(), [2]);

    $this->assertFalse($list->postSave(FALSE));
  }

  /**
   * The bundled relationship type is the fallback, and nothing is deleted.
   *
   * @covers ::postSave
   */
  public function testBundledRelationshipTypeIsTheFallback(): void {
    $this->whenGroupsExist([2]);
    $this->whenRelationshipTypeResolves();

    $relationship = $this->savableRelationship();
    $relationship->expects($this->never())->method('delete');
    $this->relationshipStorage->expects($this->once())
      ->method('create')
      ->with([
        'type' => 'flexible_group-group_node-topic',
        'gid' => 2,
        'entity_id' => 42,
      ])
      ->willReturn($relationship);

    $list = $this->computedList($this->node(), [2]);

    $this->assertFalse($list->postSave(FALSE));
  }

  /**
   * A group whose relationship type cannot be resolved creates nothing.
   *
   * @covers ::postSave
   */
  public function testUnresolvableRelationshipTypeCreatesNothing(): void {
    $this->whenGroupsExist([2]);
    $this->relationshipTypeStorage->method('getRelationshipTypeId')
      ->willReturn('flexible_group-group_node-topic');
    $this->relationshipTypeStorage->method('load')->willReturn(NULL);

    $this->relationshipStorage->expects($this->never())->method('create');

    $list = $this->computedList($this->node(), [2]);

    $this->assertFalse($list->postSave(FALSE));
  }

  /**
   * A group the entity is already in is neither re-created nor deleted.
   *
   * @covers ::postSave
   */
  public function testUnchangedGroupsCreateAndDeleteNothing(): void {
    $existing = $this->existingRelationship(2);
    $existing->expects($this->never())->method('delete');
    $this->whenEntityIsRelatedTo([$existing]);

    $this->relationshipStorage->expects($this->never())->method('create');

    $list = $this->computedList($this->node(), [2]);

    $this->assertFalse($list->postSave(FALSE));
    $this->assertFalse(FirstPublicationMarker::isRaised());
  }

  /**
   * A group dropped from the field is deleted, with the marker down.
   *
   * Deletions create no activity messages, so the raise window deliberately
   * stops before this loop.
   *
   * @covers ::postSave
   */
  public function testRemovedGroupIsDeletedWithTheMarkerDown(): void {
    $kept = $this->existingRelationship(2);
    $kept->expects($this->never())->method('delete');

    $dropped = $this->existingRelationship(3);
    $dropped->expects($this->once())
      ->method('delete')
      ->willReturnCallback(function (): void {
        $this->markerInsideDeleteLoop[] = FirstPublicationMarker::isRaised();
      });

    $this->whenEntityIsRelatedTo([$kept, $dropped]);
    $this->relationshipStorage->expects($this->never())->method('create');

    $list = $this->computedList($this->node(), [2]);

    $this->assertFalse($list->postSave(FALSE));
    $this->assertSame([FALSE], $this->markerInsideDeleteLoop);
  }

  /**
   * A failure inside the creation loop still restores the marker.
   *
   * @covers ::postSave
   */
  public function testMarkerIsRestoredWhenTheCreationLoopThrows(): void {
    $this->whenGroupsExist([2]);
    $this->whenRelationshipTypeResolves();

    $relationship = $this->createMock(GroupRelationshipInterface::class);
    $relationship->method('save')
      ->willThrowException(new \RuntimeException('Relationship failed.'));
    $this->relationshipStorage->method('create')->willReturn($relationship);

    $list = $this->computedList($this->node(), [2]);

    try {
      $list->postSave(FALSE);
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
   * This field is installed once per group type on top of the "groups" field,
   * so several instances run in the same save, and the publish replay in
   * activity_basics raises the marker around the very same save. Lowering it
   * unconditionally here would cut the enclosing fan-out short without any
   * error.
   *
   * @covers ::postSave
   */
  public function testTheMarkerRaisedByAnEnclosingSiteIsRestored(): void {
    FirstPublicationMarker::setRaised(TRUE);

    $this->whenGroupsExist([2]);
    $this->whenRelationshipTypeResolves();
    $this->relationshipStorage->method('create')
      ->willReturnCallback(fn (): GroupRelationshipInterface => $this->savableRelationship());

    $list = $this->computedList($this->node(), [2]);

    // The update path still lowers the marker for its own loop.
    $this->assertFalse($list->postSave(TRUE));
    $this->assertSame([FALSE], $this->markerInsideLoop);
    // But the enclosing site gets its marker back.
    $this->assertTrue(FirstPublicationMarker::isRaised());
  }

  /**
   * A node resolves the bundled group_node relationship plugin.
   *
   * @covers ::computeValue
   */
  public function testComputeValueForNodeUsesTheBundledNodePlugin(): void {
    $this->assertComputedPluginId($this->node(), 'group_node:topic');
  }

  /**
   * A user resolves the non-bundled group_membership plugin.
   *
   * @covers ::computeValue
   */
  public function testComputeValueForUserUsesTheMembershipPlugin(): void {
    $this->assertComputedPluginId($this->entity('user', 'user'), 'group_membership');
  }

  /**
   * Media resolves the bundled group_media plugin.
   *
   * @covers ::computeValue
   */
  public function testComputeValueForMediaUsesTheBundledMediaPlugin(): void {
    $this->assertComputedPluginId($this->entity('media', 'image'), 'group_media:image');
  }

  /**
   * An unsupported entity type is left untouched.
   *
   * @covers ::computeValue
   */
  public function testComputeValueIgnoresUnsupportedEntityTypes(): void {
    $this->entityTypeManager->expects($this->never())->method('getStorage');

    $list = $this->itemList($this->entity('comment', 'comment'), $this->fieldDefinition());
    $sentinel = [$this->item(9)];
    $this->setList($list, $sentinel);

    $list->computeValue();

    $this->assertSame($sentinel, $this->getList($list));
  }

  /**
   * An entity that has no id yet can have no relationship.
   *
   * @covers ::computeValue
   */
  public function testComputeValueReturnsEarlyForNewEntity(): void {
    $this->entityTypeManager->expects($this->never())->method('getStorage');

    $list = $this->itemList($this->node(TRUE), $this->fieldDefinition());
    $sentinel = [$this->item(9)];
    $this->setList($list, $sentinel);

    $list->computeValue();

    $this->assertSame($sentinel, $this->getList($list));
  }

  /**
   * Configured target bundles restrict the group types that are searched.
   *
   * @covers ::computeValue
   */
  public function testComputeValueHonoursConfiguredTargetBundles(): void {
    $this->groupTypeStorage->expects($this->never())->method('loadMultiple');
    $this->relationshipTypeStorage->expects($this->once())
      ->method('loadByProperties')
      ->with([
        'group_type' => ['flexible_group'],
        'content_plugin' => 'group_node:topic',
      ])
      ->willReturn([]);

    $definition = $this->fieldDefinition(['target_bundles' => ['flexible_group' => 'flexible_group']]);
    $this->itemList($this->node(), $definition)->computeValue();
  }

  /**
   * Without configured bundles every group type is searched.
   *
   * @covers ::computeValue
   */
  public function testComputeValueFallsBackToEveryGroupType(): void {
    $this->groupTypeStorage->expects($this->once())
      ->method('loadMultiple')
      ->willReturn(['flexible_group' => 'a', 'challenge' => 'b']);
    $this->relationshipTypeStorage->expects($this->once())
      ->method('loadByProperties')
      ->with([
        'group_type' => ['flexible_group', 'challenge'],
        'content_plugin' => 'group_node:topic',
      ])
      ->willReturn([]);

    $this->itemList($this->node(), $this->fieldDefinition())->computeValue();
  }

  /**
   * Without a matching relationship type the list is left as it was.
   *
   * The early return here does NOT reset the list, unlike the one taken when
   * the entity has no relationship. That asymmetry is pre-existing and is
   * pinned here so that it cannot be "tidied away" unnoticed.
   *
   * @covers ::computeValue
   */
  public function testComputeValueLeavesTheListAloneWithoutRelationshipType(): void {
    $this->whenEveryGroupTypeLoads();
    $this->relationshipTypeStorage->method('loadByProperties')->willReturn([]);
    $this->relationshipStorage->expects($this->never())->method('loadByProperties');

    $list = $this->itemList($this->node(), $this->fieldDefinition());
    $sentinel = [$this->item(9)];
    $this->setList($list, $sentinel);

    $list->computeValue();

    $this->assertSame($sentinel, $this->getList($list));
  }

  /**
   * An entity without relationships gets an empty list.
   *
   * @covers ::computeValue
   */
  public function testComputeValueEmptiesTheListWithoutRelationships(): void {
    $this->whenEveryGroupTypeLoads();
    $this->whenRelationshipTypesExist();
    $this->relationshipStorage->method('loadByProperties')->willReturn([]);

    $list = $this->itemList($this->node(), $this->fieldDefinition());
    $this->setList($list, [$this->item(9)]);

    $list->computeValue();

    $this->assertSame([], $this->getList($list));
  }

  /**
   * Every relationship becomes one item carrying its group id.
   *
   * @covers ::computeValue
   */
  public function testComputeValueBuildsOneItemPerRelationship(): void {
    $this->whenEveryGroupTypeLoads();
    $this->whenRelationshipTypesExist();
    $this->relationshipStorage->expects($this->once())
      ->method('loadByProperties')
      ->with([
        'type' => ['flexible_group-group_node-topic'],
        'entity_id' => 42,
      ])
      ->willReturn([$this->existingRelationship(2), $this->existingRelationship(3)]);

    $created = [];
    $this->fieldTypeManager->method('createFieldItem')
      ->willReturnCallback(function ($items, $index, $values) use (&$created): FieldItemInterface {
        $created[$index] = $values;

        return $this->createMock(FieldItemInterface::class);
      });

    $list = $this->itemList($this->node(), $this->fieldDefinition());
    $this->setList($list, [$this->item(9)]);

    $list->computeValue();

    $this->assertSame([
      ['target_id' => 2],
      ['target_id' => 3],
    ], $created);
    $this->assertCount(2, $this->getList($list));
  }

  /**
   * Builds the item list under test.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity the field belongs to.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface|null $definition
   *   (optional) The data definition, needed only by computeValue().
   *
   * @return \Drupal\social_group\Plugin\Field\SocialGroupReferenceItemList
   *   The item list.
   */
  private function itemList(ContentEntityInterface $entity, ?DataDefinitionInterface $definition = NULL): SocialGroupReferenceItemList {
    // FieldItemList::getEntity() unwraps the parent typed data object, and
    // TypedData::__construct() stores the parent as given, so no further
    // wiring is needed to make getEntity() answer.
    $parent = $this->createMock(TypedDataInterface::class);
    $parent->method('getValue')->willReturn($entity);

    return new SocialGroupReferenceItemList(
      $definition ?? $this->createMock(DataDefinitionInterface::class),
      'groups',
      $parent
    );
  }

  /**
   * Builds an item list whose value was computed to the given group ids.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity the field belongs to.
   * @param int[] $gids
   *   The group ids the field holds.
   *
   * @return \Drupal\social_group\Plugin\Field\SocialGroupReferenceItemList
   *   The item list.
   */
  private function computedList(ContentEntityInterface $entity, array $gids): SocialGroupReferenceItemList {
    $list = $this->itemList($entity);

    $this->setList($list, array_map(fn (int $gid): FieldItemInterface => $this->item($gid), $gids));
    // setValue() would set this flag for free, but it routes through the field
    // type plugin manager; reflection keeps the container minimal.
    $property = new \ReflectionProperty(SocialGroupReferenceItemList::class, 'valueComputed');
    $property->setValue($list, TRUE);

    return $list;
  }

  /**
   * Replaces the raw item list.
   *
   * @param \Drupal\social_group\Plugin\Field\SocialGroupReferenceItemList $list
   *   The item list to write to.
   * @param \Drupal\Core\Field\FieldItemInterface[] $items
   *   The items to store.
   */
  private function setList(SocialGroupReferenceItemList $list, array $items): void {
    $property = new \ReflectionProperty(SocialGroupReferenceItemList::class, 'list');
    $property->setValue($list, $items);
  }

  /**
   * Reads the raw item list.
   *
   * @param \Drupal\social_group\Plugin\Field\SocialGroupReferenceItemList $list
   *   The item list to read from.
   *
   * @return \Drupal\Core\Field\FieldItemInterface[]
   *   The stored items.
   */
  private function getList(SocialGroupReferenceItemList $list): array {
    $property = new \ReflectionProperty(SocialGroupReferenceItemList::class, 'list');

    return $property->getValue($list);
  }

  /**
   * Builds a field item holding a group id.
   *
   * @param int $gid
   *   The group id the item points at.
   *
   * @return \Drupal\Core\Field\FieldItemInterface&\PHPUnit\Framework\MockObject\MockObject
   *   The item mock.
   */
  private function item(int $gid): FieldItemInterface&MockObject {
    $target_id = $this->createMock(TypedDataInterface::class);
    $target_id->method('getValue')->willReturn($gid);

    $item = $this->createMock(FieldItemInterface::class);
    $item->method('get')->with('target_id')->willReturn($target_id);

    return $item;
  }

  /**
   * Builds the entity the field belongs to.
   *
   * @param string $entity_type_id
   *   The entity type id the mock reports.
   * @param string $bundle
   *   The bundle the mock reports.
   * @param bool $is_new
   *   (optional) TRUE to report the entity as not saved yet.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface&\PHPUnit\Framework\MockObject\MockObject
   *   The entity mock.
   */
  private function entity(string $entity_type_id, string $bundle, bool $is_new = FALSE): ContentEntityInterface&MockObject {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn($entity_type_id);
    $entity->method('bundle')->willReturn($bundle);
    $entity->method('id')->willReturn(42);
    $entity->method('isNew')->willReturn($is_new);

    return $entity;
  }

  /**
   * Builds the topic node the field belongs to.
   *
   * @param bool $is_new
   *   (optional) TRUE to report the node as not saved yet.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface&\PHPUnit\Framework\MockObject\MockObject
   *   The node mock.
   */
  private function node(bool $is_new = FALSE): ContentEntityInterface&MockObject {
    return $this->entity('node', 'topic', $is_new);
  }

  /**
   * Builds a group mock.
   *
   * @param int $gid
   *   The id of the group.
   *
   * @return \Drupal\group\Entity\GroupInterface&\PHPUnit\Framework\MockObject\MockObject
   *   The group mock.
   */
  private function group(int $gid): GroupInterface&MockObject {
    $group = $this->createMock(GroupInterface::class);
    $group->method('id')->willReturn($gid);
    $group->method('bundle')->willReturn('flexible_group');

    return $group;
  }

  /**
   * Makes Group::load() resolve the given group ids.
   *
   * @param int[] $gids
   *   The group ids that load successfully.
   */
  private function whenGroupsExist(array $gids): void {
    $map = [];

    foreach ($gids as $gid) {
      $map[] = [$gid, $this->group($gid)];
    }

    $this->groupStorage->method('load')->willReturnMap($map);
  }

  /**
   * Makes only the bundled relationship type resolve.
   */
  private function whenRelationshipTypeResolves(): void {
    $this->relationshipTypeStorage->method('getRelationshipTypeId')->willReturnMap([
      ['flexible_group', 'group_node', 'flexible_group-group_node'],
      ['flexible_group', 'group_node:topic', 'flexible_group-group_node-topic'],
    ]);
    $this->relationshipTypeStorage->method('load')->willReturnMap([
      ['flexible_group-group_node', NULL],
      ['flexible_group-group_node-topic', $this->relationshipType('flexible_group-group_node-topic')],
    ]);
  }

  /**
   * Makes the fallback lookup of every group type answer.
   *
   * Only the branch taken when the field configures no target bundles reaches
   * it, so it is wired per test rather than in setUp(), where it would shadow
   * the expectation of testComputeValueFallsBackToEveryGroupType().
   */
  private function whenEveryGroupTypeLoads(): void {
    $this->groupTypeStorage->method('loadMultiple')
      ->willReturn(['flexible_group' => 'a']);
  }

  /**
   * Makes computeValue() find a matching relationship type.
   */
  private function whenRelationshipTypesExist(): void {
    $this->relationshipTypeStorage->method('loadByProperties')
      ->willReturn(['flexible_group-group_node-topic' => 'type']);
  }

  /**
   * Builds a relationship type mock.
   *
   * @param string $id
   *   The id of the relationship type.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface&\PHPUnit\Framework\MockObject\MockObject
   *   The relationship type mock.
   */
  private function relationshipType(string $id): ContentEntityInterface&MockObject {
    $type = $this->createMock(ContentEntityInterface::class);
    $type->method('id')->willReturn($id);

    return $type;
  }

  /**
   * Builds a relationship that records the marker state when it is saved.
   *
   * @return \Drupal\group\Entity\GroupRelationshipInterface&\PHPUnit\Framework\MockObject\MockObject
   *   The relationship mock.
   */
  private function savableRelationship(): GroupRelationshipInterface&MockObject {
    $relationship = $this->createMock(GroupRelationshipInterface::class);
    $relationship->expects($this->once())
      ->method('save')
      ->willReturnCallback(function (): void {
        $this->markerInsideLoop[] = FirstPublicationMarker::isRaised();
      });

    return $relationship;
  }

  /**
   * Builds a relationship pointing at the group with the given id.
   *
   * @param int $gid
   *   The id of the group the relationship belongs to.
   *
   * @return \Drupal\group\Entity\GroupRelationshipInterface&\PHPUnit\Framework\MockObject\MockObject
   *   The relationship mock.
   */
  private function existingRelationship(int $gid): GroupRelationshipInterface&MockObject {
    $relationship = $this->createMock(GroupRelationshipInterface::class);
    $relationship->method('getGroup')->willReturn($this->group($gid));
    $relationship->method('getGroupId')->willReturn($gid);

    return $relationship;
  }

  /**
   * Makes the relationship storage report the entity's relationships.
   *
   * @param \Drupal\group\Entity\GroupRelationshipInterface[] $relationships
   *   The relationships the entity already has.
   */
  private function whenEntityIsRelatedTo(array $relationships): void {
    $this->relationshipStorage = $this->createMock(GroupRelationshipStorageInterface::class);
    $this->relationshipStorage->method('loadByEntity')->willReturn($relationships);
  }

  /**
   * Builds a field definition carrying the given handler settings.
   *
   * @param array|null $handler_settings
   *   (optional) The handler settings, or NULL when none are configured.
   *
   * @return \Drupal\Core\TypedData\ListDataDefinitionInterface&\PHPUnit\Framework\MockObject\MockObject
   *   The definition mock.
   */
  private function fieldDefinition(?array $handler_settings = NULL): ListDataDefinitionInterface&MockObject {
    $item_definition = $this->createMock(DataDefinitionInterface::class);
    $item_definition->method('getSetting')
      ->with('handler_settings')
      ->willReturn($handler_settings);

    $definition = $this->createMock(ListDataDefinitionInterface::class);
    $definition->method('getItemDefinition')->willReturn($item_definition);

    return $definition;
  }

  /**
   * Asserts which relationship plugin id an entity type resolves to.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity the field belongs to.
   * @param string $plugin_id
   *   The expected relationship plugin id.
   */
  private function assertComputedPluginId(ContentEntityInterface $entity, string $plugin_id): void {
    $this->relationshipTypeStorage->expects($this->once())
      ->method('loadByProperties')
      ->with([
        'group_type' => ['flexible_group'],
        'content_plugin' => $plugin_id,
      ])
      ->willReturn([]);

    $definition = $this->fieldDefinition(['target_bundles' => ['flexible_group' => 'flexible_group']]);
    $this->itemList($entity, $definition)->computeValue();
  }

}

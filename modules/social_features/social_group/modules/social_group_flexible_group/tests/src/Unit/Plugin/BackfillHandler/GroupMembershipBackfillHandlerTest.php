<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group_flexible_group\Unit\Plugin\BackfillHandler;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\group\Entity\GroupMembershipInterface;
use Drupal\social_group_flexible_group\Plugin\BackfillHandler\GroupMembershipBackfillHandler;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Unit tests for GroupMembershipBackfillHandler.
 *
 * @coversDefaultClass \Drupal\social_group_flexible_group\Plugin\BackfillHandler\GroupMembershipBackfillHandler
 * @group social_group_flexible_group
 */
final class GroupMembershipBackfillHandlerTest extends UnitTestCase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityFieldManager;

  /**
   * The account switcher service.
   */
  protected AccountSwitcherInterface $accountSwitcher;

  /**
   * The container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityFieldManager = $this->createMock(EntityFieldManagerInterface::class);
    $this->accountSwitcher = $this->createMock(AccountSwitcherInterface::class);
    $this->container = $this->createMock(ContainerInterface::class);
  }

  /**
   * Creates a GroupMembershipBackfillHandler plugin instance.
   */
  private function createPlugin(): GroupMembershipBackfillHandler {
    $plugin_definition = [
      'id' => 'group_membership',
      'label' => 'Group Membership',
      'entity_type' => 'group_content',
      'handler_service' => 'social_group_flexible_group.group_membership.eda_handler',
      'handler_method' => 'groupMembershipCreate',
    ];

    return new GroupMembershipBackfillHandler(
      [],
      'group_membership',
      $plugin_definition,
      $this->entityTypeManager,
      $this->entityFieldManager,
      $this->accountSwitcher,
      $this->container
    );
  }

  /**
   * Tests plugin creation.
   *
   * @covers ::__construct
   */
  public function testCreate(): void {
    $plugin = $this->createPlugin();
    // Verify plugin ID is set correctly.
    $this->assertEquals('group_membership', $plugin->getPluginId());
  }

  /**
   * Tests process() calls groupMembershipCreate on EdaHandler.
   *
   * @covers ::process
   */
  public function testProcess(): void {
    $membership = $this->createMock(GroupMembershipInterface::class);
    // EdaHandler is final, so we create a test double that implements
    // the method.
    $eda_handler = new class() {
      /**
       * Whether groupMembershipCreate was called.
       */
      public bool $groupMembershipCreateCalled = FALSE;

      /**
       * The membership passed to groupMembershipCreate.
       */
      public ?GroupMembershipInterface $membershipPassed = NULL;

      /**
       * Test handler method.
       */
      public function groupMembershipCreate(GroupMembershipInterface $membership): void {
        $this->groupMembershipCreateCalled = TRUE;
        $this->membershipPassed = $membership;
      }

    };

    $this->container->expects($this->once())
      ->method('get')
      ->with('social_group_flexible_group.group_membership.eda_handler')
      ->willReturn($eda_handler);

    $plugin = $this->createPlugin();
    $plugin->process($membership);

    $this->assertTrue($eda_handler->groupMembershipCreateCalled);
    $this->assertSame($membership, $eda_handler->membershipPassed);
  }

  /**
   * Tests process() throws exception for non-membership entity.
   *
   * @covers ::process
   */
  public function testProcessThrowsExceptionForInvalidEntity(): void {
    $entity = $this->createMock(EntityInterface::class);

    $plugin = $this->createPlugin();
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Expected GroupMembershipInterface');
    $plugin->process($entity);
  }

  /**
   * Tests getEntityIds() queries group memberships with correct filters.
   *
   * @covers ::getEntityIds
   */
  public function testGetEntityIds(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('accessCheck')
      ->with(FALSE)
      ->willReturnSelf();
    $conditions = [];
    $query->expects($this->exactly(2))
      ->method('condition')
      ->willReturnCallback(function ($field, $value, $operator = NULL) use ($query, &$conditions) {
        $conditions[] = [$field, $value, $operator];
        return $query;
      });
    $query->expects($this->once())
      ->method('execute')
      ->willReturn(['1' => '1', '2' => '2', '3' => '3']);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('getQuery')
      ->willReturn($query);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('group_content')
      ->willReturn($storage);

    $plugin = $this->createPlugin();
    $result = $plugin->getEntityIds();

    // Entity queries return associative arrays with entity IDs as keys.
    $this->assertEquals(['1' => '1', '2' => '2', '3' => '3'], $result);

    // Verify all expected conditions were applied, regardless of order.
    // Bundle condition should NOT be added since bundle is empty.
    $this->assertCount(2, $conditions);
    $this->assertContains(['plugin_id', 'group_membership', NULL], $conditions);
    $this->assertContains(['group_type', 'flexible_group', NULL], $conditions);
  }

  /**
   * Tests getEntityIds() with date range filters.
   *
   * @covers ::getEntityIds
   */
  public function testGetEntityIdsWithDateRange(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('accessCheck')
      ->with(FALSE)
      ->willReturnSelf();
    $conditions = [];
    $query->expects($this->exactly(4))
      ->method('condition')
      ->willReturnCallback(function ($field, $value, $operator = NULL) use ($query, &$conditions) {
        $conditions[] = [$field, $value, $operator];
        return $query;
      });
    $query->expects($this->once())
      ->method('execute')
      ->willReturn(['2' => '2']);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('getQuery')
      ->willReturn($query);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('group_content')
      ->willReturn($storage);

    // Mock entity field manager to return 'created' field definition.
    $this->entityFieldManager->expects($this->once())
      ->method('getFieldStorageDefinitions')
      ->with('group_content')
      ->willReturn(['created' => $this->createMock(FieldStorageDefinitionInterface::class)]);

    $plugin = $this->createPlugin();
    $result = $plugin->getEntityIds(1000, 2000);

    // Entity queries return associative arrays with entity IDs as keys.
    $this->assertEquals(['2' => '2'], $result);

    // Verify all expected conditions were applied, regardless of order.
    // Bundle condition should NOT be added since bundle is empty.
    $this->assertCount(4, $conditions);
    $this->assertContains(['plugin_id', 'group_membership', NULL], $conditions);
    $this->assertContains(['group_type', 'flexible_group', NULL], $conditions);
    $this->assertContains(['created', 1000, '>='], $conditions);
    $this->assertContains(['created', 2000, '<='], $conditions);
  }

  /**
   * Tests getEntityIds() returns empty array when no results.
   *
   * @covers ::getEntityIds
   */
  public function testGetEntityIdsReturnsEmptyArray(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('accessCheck')
      ->with(FALSE)
      ->willReturnSelf();
    $query->expects($this->exactly(2))
      ->method('condition')
      ->willReturnSelf();
    $query->expects($this->once())
      ->method('execute')
      ->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('getQuery')
      ->willReturn($query);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('group_content')
      ->willReturn($storage);

    $plugin = $this->createPlugin();
    $result = $plugin->getEntityIds();

    // Should return empty array, not FALSE.
    $this->assertEquals([], $result);
  }

  /**
   * Tests process() throws exception when handler method is not callable.
   *
   * @covers ::process
   */
  public function testProcessThrowsExceptionForWrongHandlerType(): void {
    $membership = $this->createMock(GroupMembershipInterface::class);
    $wrong_handler = new \stdClass();

    $this->container->expects($this->once())
      ->method('get')
      ->with('social_group_flexible_group.group_membership.eda_handler')
      ->willReturn($wrong_handler);

    $plugin = $this->createPlugin();
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('does not have callable method "groupMembershipCreate"');
    $plugin->process($membership);
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group_flexible_group\Unit\Plugin\BackfillHandler;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\social_group_flexible_group\Plugin\BackfillHandler\GroupBackfillHandler;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Unit tests for GroupBackfillHandler.
 *
 * @coversDefaultClass \Drupal\social_group_flexible_group\Plugin\BackfillHandler\GroupBackfillHandler
 * @group social_group_flexible_group
 */
final class GroupBackfillHandlerTest extends UnitTestCase {

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
   * Creates a GroupBackfillHandler plugin instance.
   */
  private function createPlugin(): GroupBackfillHandler {
    $plugin_definition = [
      'id' => 'group',
      'label' => 'Group',
      'entity_type' => 'group',
      'bundle' => 'flexible_group',
      'handler_service' => 'social_group_flexible_group.eda_handler',
      'handler_method' => 'groupCreate',
    ];

    return new GroupBackfillHandler(
      [],
      'group',
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
    $this->assertEquals('group', $plugin->getPluginId());
  }

  /**
   * Tests process() calls groupCreate on EdaHandler.
   *
   * @covers ::process
   */
  public function testProcess(): void {
    $group = $this->createMock(GroupInterface::class);
    $group->method('getOwner')
      ->willReturn($this->createMock(User::class));
    // EdaHandler is final, so we create a test double that implements
    // the method.
    $eda_handler = new class() {
      /**
       * Whether groupCreate was called.
       */
      public bool $groupCreateCalled = FALSE;

      /**
       * The group passed to groupCreate.
       */
      public ?GroupInterface $groupPassed = NULL;

      /**
       * Test handler method.
       */
      public function groupCreate(GroupInterface $group): void {
        $this->groupCreateCalled = TRUE;
        $this->groupPassed = $group;
      }

    };

    $this->container->expects($this->once())
      ->method('get')
      ->with('social_group_flexible_group.eda_handler')
      ->willReturn($eda_handler);

    $plugin = $this->createPlugin();
    $plugin->process($group);

    $this->assertTrue($eda_handler->groupCreateCalled);
    $this->assertSame($group, $eda_handler->groupPassed);
  }

  /**
   * Tests getEntityIds() queries groups with bundle filtering.
   *
   * @covers ::getEntityIds
   */
  public function testGetEntityIds(): void {
    $entity_type = $this->createMock(EntityTypeInterface::class);
    $entity_type->expects($this->once())
      ->method('hasKey')
      ->with('bundle')
      ->willReturn(TRUE);
    $entity_type->expects($this->once())
      ->method('getKey')
      ->with('bundle')
      ->willReturn('type');

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
      ->with('group')
      ->willReturn($storage);
    $this->entityTypeManager->expects($this->once())
      ->method('getDefinition')
      ->with('group')
      ->willReturn($entity_type);

    // Mock entity field manager to return 'created' field definition.
    $this->entityFieldManager->expects($this->once())
      ->method('getFieldStorageDefinitions')
      ->with('group')
      ->willReturn(['created' => $this->createMock(FieldStorageDefinitionInterface::class)]);

    $plugin = $this->createPlugin();
    $result = $plugin->getEntityIds(1000, NULL);

    // Entity queries return associative arrays with entity IDs as keys.
    $this->assertEquals(['1' => '1', '2' => '2', '3' => '3'], $result);

    // Verify all expected conditions were applied, regardless of order.
    $this->assertCount(2, $conditions);
    $this->assertContains(['type', 'flexible_group', NULL], $conditions);
    $this->assertContains(['created', 1000, '>='], $conditions);
  }

  /**
   * Tests getEntityIds() with date range filters.
   *
   * @covers ::getEntityIds
   */
  public function testGetEntityIdsWithDateRange(): void {
    $entity_type = $this->createMock(EntityTypeInterface::class);
    $entity_type->expects($this->once())
      ->method('hasKey')
      ->with('bundle')
      ->willReturn(TRUE);
    $entity_type->expects($this->once())
      ->method('getKey')
      ->with('bundle')
      ->willReturn('type');

    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('accessCheck')
      ->with(FALSE)
      ->willReturnSelf();
    $conditions = [];
    $query->expects($this->exactly(3))
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
      ->with('group')
      ->willReturn($storage);
    $this->entityTypeManager->expects($this->once())
      ->method('getDefinition')
      ->with('group')
      ->willReturn($entity_type);

    // Mock entity field manager to return 'created' field definition.
    $this->entityFieldManager->expects($this->once())
      ->method('getFieldStorageDefinitions')
      ->with('group')
      ->willReturn(['created' => $this->createMock(FieldStorageDefinitionInterface::class)]);

    $plugin = $this->createPlugin();
    $result = $plugin->getEntityIds(1000, 2000);

    // Entity queries return associative arrays with entity IDs as keys.
    $this->assertEquals(['2' => '2'], $result);

    // Verify all expected conditions were applied, regardless of order.
    $this->assertCount(3, $conditions);
    $this->assertContains(['type', 'flexible_group', NULL], $conditions);
    $this->assertContains(['created', 1000, '>='], $conditions);
    $this->assertContains(['created', 2000, '<='], $conditions);
  }

}

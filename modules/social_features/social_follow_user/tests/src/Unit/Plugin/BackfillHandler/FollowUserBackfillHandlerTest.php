<?php

declare(strict_types=1);

namespace Drupal\Tests\social_follow_user\Unit\Plugin\BackfillHandler;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\flag\FlaggingInterface;
use Drupal\social_follow_user\Plugin\BackfillHandler\FollowUserBackfillHandler;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Unit tests for FollowUserBackfillHandler.
 *
 * @coversDefaultClass \Drupal\social_follow_user\Plugin\BackfillHandler\FollowUserBackfillHandler
 * @group social_follow_user
 */
final class FollowUserBackfillHandlerTest extends UnitTestCase {

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
   * Creates a FollowUserBackfillHandler plugin instance.
   */
  private function createPlugin(): FollowUserBackfillHandler {
    $plugin_definition = [
      'id' => 'follow_user',
      'label' => 'Follow User',
      'entity_type' => 'flagging',
      'bundle' => 'follow_user',
      'handler_service' => 'social_follow_user.eda_handler',
      'handler_method' => 'followUserCreate',
    ];

    return new FollowUserBackfillHandler(
      [],
      'follow_user',
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
    $this->assertEquals('follow_user', $plugin->getPluginId());
  }

  /**
   * Tests process() calls followUserCreate on EdaHandler.
   *
   * @covers ::process
   */
  public function testProcess(): void {
    $flagging = $this->createMock(FlaggingInterface::class);
    $flagging->method('getOwner')
      ->willReturn($this->createMock(User::class));
    // EdaHandler is final, so we create a test double that implements
    // the method.
    $eda_handler = new class() {
      /**
       * Whether followUserCreate was called.
       */
      public bool $followUserCreateCalled = FALSE;

      /**
       * The flagging passed to followUserCreate.
       */
      public ?FlaggingInterface $flaggingPassed = NULL;

      /**
       * Test handler method.
       */
      public function followUserCreate(FlaggingInterface $flagging): void {
        $this->followUserCreateCalled = TRUE;
        $this->flaggingPassed = $flagging;
      }

    };

    $this->container->expects($this->once())
      ->method('get')
      ->with('social_follow_user.eda_handler')
      ->willReturn($eda_handler);

    $plugin = $this->createPlugin();
    $plugin->process($flagging);

    $this->assertTrue($eda_handler->followUserCreateCalled);
    $this->assertSame($flagging, $eda_handler->flaggingPassed);
  }

  /**
   * Tests getEntityIds() queries flaggings filtered by flag_id.
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
      ->with('flagging')
      ->willReturn($storage);

    // Mock entity field manager to return 'created' field definition.
    $this->entityFieldManager->expects($this->once())
      ->method('getFieldStorageDefinitions')
      ->with('flagging')
      ->willReturn(['created' => $this->createMock(FieldStorageDefinitionInterface::class)]);

    $plugin = $this->createPlugin();
    $result = $plugin->getEntityIds(1000, NULL);

    // Entity queries return associative arrays with entity IDs as keys.
    $this->assertEquals(['1' => '1', '2' => '2', '3' => '3'], $result);

    // Verify all expected conditions were applied, regardless of order.
    $this->assertCount(2, $conditions);
    $this->assertContains(['flag_id', 'follow_user', NULL], $conditions, 'flag_id condition should use bundle from plugin definition');
    $this->assertContains(['created', 1000, '>='], $conditions);
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
      ->with('flagging')
      ->willReturn($storage);

    // Mock entity field manager to return 'created' field definition.
    $this->entityFieldManager->expects($this->once())
      ->method('getFieldStorageDefinitions')
      ->with('flagging')
      ->willReturn(['created' => $this->createMock(FieldStorageDefinitionInterface::class)]);

    $plugin = $this->createPlugin();
    $result = $plugin->getEntityIds(1000, 2000);

    // Entity queries return associative arrays with entity IDs as keys.
    $this->assertEquals(['2' => '2'], $result);

    // Verify all expected conditions were applied, regardless of order.
    $this->assertCount(3, $conditions);
    $this->assertContains(['flag_id', 'follow_user', NULL], $conditions, 'flag_id condition should use bundle from plugin definition');
    $this->assertContains(['created', 1000, '>='], $conditions);
    $this->assertContains(['created', 2000, '<='], $conditions);
  }

  /**
   * Tests getEntityIds() without date filters.
   *
   * @covers ::getEntityIds
   */
  public function testGetEntityIdsWithoutDateFilters(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('accessCheck')
      ->with(FALSE)
      ->willReturnSelf();
    $query->expects($this->once())
      ->method('condition')
      ->with('flag_id', 'follow_user')
      ->willReturnSelf();
    $query->expects($this->once())
      ->method('execute')
      ->willReturn(['1' => '1', '2' => '2']);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('getQuery')
      ->willReturn($query);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('flagging')
      ->willReturn($storage);

    $plugin = $this->createPlugin();
    $result = $plugin->getEntityIds();

    // Entity queries return associative arrays with entity IDs as keys.
    $this->assertEquals(['1' => '1', '2' => '2'], $result);
  }

  /**
   * Tests getEntityIds() throws exception when 'created' field is missing.
   *
   * @covers ::getEntityIds
   */
  public function testGetEntityIdsThrowsWhenCreatedFieldMissing(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('accessCheck')
      ->with(FALSE)
      ->willReturnSelf();
    $query->expects($this->once())
      ->method('condition')
      ->with('flag_id', 'follow_user')
      ->willReturnSelf();

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('getQuery')
      ->willReturn($query);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('flagging')
      ->willReturn($storage);

    // Mock entity field manager to return empty field definitions (no
    // 'created' field).
    $this->entityFieldManager->expects($this->once())
      ->method('getFieldStorageDefinitions')
      ->with('flagging')
      ->willReturn([]);

    $plugin = $this->createPlugin();

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Entity type "flagging" does not have a "created" field');
    $plugin->getEntityIds(1000, NULL);
  }

  /**
   * Tests getEntityIds() throws exception when query returns non-array.
   *
   * @covers ::getEntityIds
   */
  public function testGetEntityIdsThrowsWhenQueryReturnsNonArray(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('accessCheck')
      ->with(FALSE)
      ->willReturnSelf();
    $query->expects($this->once())
      ->method('condition')
      ->with('flag_id', 'follow_user')
      ->willReturnSelf();
    $query->expects($this->once())
      ->method('execute')
      ->willReturn('not-an-array');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('getQuery')
      ->willReturn($query);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('flagging')
      ->willReturn($storage);

    $plugin = $this->createPlugin();

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Entity query execute() must return an array for entity type "flagging", got string');
    $plugin->getEntityIds();
  }

}

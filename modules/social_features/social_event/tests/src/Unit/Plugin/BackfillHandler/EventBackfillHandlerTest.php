<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event\Unit\Plugin\BackfillHandler;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\node\NodeInterface;
use Drupal\social_event\Plugin\BackfillHandler\EventBackfillHandler;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Unit tests for EventBackfillHandler.
 *
 * @coversDefaultClass \Drupal\social_event\Plugin\BackfillHandler\EventBackfillHandler
 * @group social_event
 */
final class EventBackfillHandlerTest extends UnitTestCase {

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
   * Creates an EventBackfillHandler plugin instance.
   */
  private function createPlugin(): EventBackfillHandler {
    $plugin_definition = [
      'id' => 'event',
      'label' => 'Event',
      'entity_type' => 'node',
      'bundle' => 'event',
      'handler_service' => 'social_event.eda_handler',
      'handler_method' => 'eventCreate',
    ];

    return new EventBackfillHandler(
      [],
      'event',
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
    $this->assertEquals('event', $plugin->getPluginId());
  }

  /**
   * Tests process() calls eventCreate on EdaHandler.
   *
   * @covers ::process
   */
  public function testProcess(): void {
    $node = $this->createMock(NodeInterface::class);
    $node->method('getOwner')
      ->willReturn($this->createMock(User::class));
    // EdaHandler is final, so we create a test double that implements
    // the method.
    $eda_handler = new class() {
      /**
       * Whether eventCreate was called.
       */
      public bool $eventCreateCalled = FALSE;

      /**
       * The node passed to eventCreate.
       */
      public ?NodeInterface $nodePassed = NULL;

      /**
       * Test handler method.
       */
      public function eventCreate(NodeInterface $node): void {
        $this->eventCreateCalled = TRUE;
        $this->nodePassed = $node;
      }

    };

    $this->container->expects($this->once())
      ->method('get')
      ->with('social_event.eda_handler')
      ->willReturn($eda_handler);

    $plugin = $this->createPlugin();
    $plugin->process($node);

    $this->assertTrue($eda_handler->eventCreateCalled);
    $this->assertSame($node, $eda_handler->nodePassed);
  }

  /**
   * Tests getEntityIds() queries events with bundle filtering.
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
    $query->expects($this->exactly(2))
      ->method('condition')
      ->willReturnCallback(function ($field, $value, $operator = NULL) use ($query) {
        if ($field === 'type') {
          $this->assertEquals('event', $value);
        }
        elseif ($field === 'created') {
          $this->assertEquals(1000, $value);
          $this->assertEquals('>=', $operator);
        }
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
      ->with('node')
      ->willReturn($storage);
    $this->entityTypeManager->expects($this->once())
      ->method('getDefinition')
      ->with('node')
      ->willReturn($entity_type);

    // Mock entity field manager to return 'created' field definition.
    $this->entityFieldManager->expects($this->once())
      ->method('getFieldStorageDefinitions')
      ->with('node')
      ->willReturn(['created' => $this->createMock(FieldStorageDefinitionInterface::class)]);

    $plugin = $this->createPlugin();
    $result = $plugin->getEntityIds(1000, NULL);

    // Entity queries return associative arrays with entity IDs as keys.
    $this->assertEquals(['1' => '1', '2' => '2', '3' => '3'], $result);
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
    $call_count = 0;
    $query->expects($this->exactly(3))
      ->method('condition')
      ->willReturnCallback(function ($field, $value, $operator = NULL) use (&$call_count, $query) {
        $call_count++;
        if ($call_count === 1) {
          $this->assertEquals('type', $field);
          $this->assertEquals('event', $value);
        }
        elseif ($call_count === 2) {
          $this->assertEquals('created', $field);
          $this->assertEquals(1000, $value);
          $this->assertEquals('>=', $operator);
        }
        elseif ($call_count === 3) {
          $this->assertEquals('created', $field);
          $this->assertEquals(2000, $value);
          $this->assertEquals('<=', $operator);
        }
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
      ->with('node')
      ->willReturn($storage);
    $this->entityTypeManager->expects($this->once())
      ->method('getDefinition')
      ->with('node')
      ->willReturn($entity_type);

    // Mock entity field manager to return 'created' field definition.
    $this->entityFieldManager->expects($this->once())
      ->method('getFieldStorageDefinitions')
      ->with('node')
      ->willReturn(['created' => $this->createMock(FieldStorageDefinitionInterface::class)]);

    $plugin = $this->createPlugin();
    $result = $plugin->getEntityIds(1000, 2000);

    // Entity queries return associative arrays with entity IDs as keys.
    $this->assertEquals(['2' => '2'], $result);
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\social_topic\Unit\Plugin\BackfillHandler;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\node\NodeInterface;
use Drupal\social_topic\Plugin\BackfillHandler\TopicBackfillHandler;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Unit tests for TopicBackfillHandler.
 *
 * @coversDefaultClass \Drupal\social_topic\Plugin\BackfillHandler\TopicBackfillHandler
 * @group social_topic
 */
final class TopicBackfillHandlerTest extends UnitTestCase {

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
   * Creates a TopicBackfillHandler plugin instance.
   */
  private function createPlugin(): TopicBackfillHandler {
    $plugin_definition = [
      'id' => 'topic',
      'label' => 'Topic',
      'entity_type' => 'node',
      'bundle' => 'topic',
      'handler_service' => 'social_topic.eda_handler',
      'handler_method' => 'topicCreate',
    ];

    return new TopicBackfillHandler(
      [],
      'topic',
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
    $this->assertEquals('topic', $plugin->getPluginId());
  }

  /**
   * Tests process() calls topicCreate on EdaHandler.
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
       * Whether topicCreate was called.
       */
      public bool $topicCreateCalled = FALSE;

      /**
       * The node passed to topicCreate.
       */
      public ?NodeInterface $nodePassed = NULL;

      /**
       * Test handler method.
       */
      public function topicCreate(NodeInterface $node): void {
        $this->topicCreateCalled = TRUE;
        $this->nodePassed = $node;
      }

    };

    $this->container->expects($this->once())
      ->method('get')
      ->with('social_topic.eda_handler')
      ->willReturn($eda_handler);

    $plugin = $this->createPlugin();
    $plugin->process($node);

    $this->assertTrue($eda_handler->topicCreateCalled);
    $this->assertSame($node, $eda_handler->nodePassed);
  }

  /**
   * Tests getEntityIds() queries topics with bundle filtering.
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

    // Verify all expected conditions were applied, regardless of order.
    $this->assertCount(2, $conditions);
    $this->assertContains(['type', 'topic', NULL], $conditions);
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

    // Verify all expected conditions were applied, regardless of order.
    $this->assertCount(3, $conditions);
    $this->assertContains(['type', 'topic', NULL], $conditions);
    $this->assertContains(['created', 1000, '>='], $conditions);
    $this->assertContains(['created', 2000, '<='], $conditions);
  }

}

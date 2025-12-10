<?php

declare(strict_types=1);

namespace Drupal\Tests\social_comment\Unit\Plugin\BackfillHandler;

use Drupal\comment\CommentInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\social_eda\Plugin\BackfillHandlerBase;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base test class for comment backfill handler tests.
 */
abstract class CommentBackfillHandlerTestBase extends UnitTestCase {

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
    $this->container = $this->createMock(ContainerInterface::class);
  }

  /**
   * Gets the plugin ID.
   *
   * @return string
   *   The plugin ID.
   */
  abstract protected function getPluginId(): string;

  /**
   * Gets the bundle name.
   *
   * @return string
   *   The bundle name.
   */
  abstract protected function getBundle(): string;

  /**
   * Gets the plugin label.
   *
   * @return string
   *   The plugin label.
   */
  abstract protected function getLabel(): string;

  /**
   * Creates a plugin instance.
   *
   * @return \Drupal\social_eda\Plugin\BackfillHandlerBase
   *   The plugin instance.
   */
  abstract protected function createPlugin(): BackfillHandlerBase;

  /**
   * Tests plugin creation.
   */
  public function testCreate(): void {
    $plugin = $this->createPlugin();
    // Verify plugin ID is set correctly.
    $this->assertEquals($this->getPluginId(), $plugin->getPluginId());
  }

  /**
   * Tests process() calls commentCreate on EdaHandler.
   */
  public function testProcess(): void {
    $comment = $this->createMock(CommentInterface::class);
    // EdaHandler is final, so we create a test double that implements
    // the method.
    $eda_handler = new class() {
      /**
       * Whether commentCreate was called.
       */
      public bool $commentCreateCalled = FALSE;

      /**
       * The comment passed to commentCreate.
       */
      public ?CommentInterface $commentPassed = NULL;

      /**
       * Test handler method.
       */
      public function commentCreate(CommentInterface $comment): void {
        $this->commentCreateCalled = TRUE;
        $this->commentPassed = $comment;
      }

    };

    $this->container->expects($this->once())
      ->method('get')
      ->with('social_comment.eda_handler')
      ->willReturn($eda_handler);

    $plugin = $this->createPlugin();
    $plugin->process($comment);

    $this->assertTrue($eda_handler->commentCreateCalled);
    $this->assertSame($comment, $eda_handler->commentPassed);
  }

  /**
   * Tests getEntityIds() queries comments with bundle filtering.
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
      ->willReturn('comment_type');

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
      ->with('comment')
      ->willReturn($storage);
    $this->entityTypeManager->expects($this->once())
      ->method('getDefinition')
      ->with('comment')
      ->willReturn($entity_type);

    // Mock entity field manager to return 'created' field definition.
    $this->entityFieldManager->expects($this->once())
      ->method('getFieldStorageDefinitions')
      ->with('comment')
      ->willReturn(['created' => $this->createMock(FieldStorageDefinitionInterface::class)]);

    $plugin = $this->createPlugin();
    $result = $plugin->getEntityIds(1000, NULL);

    // Entity queries return associative arrays with entity IDs as keys.
    $this->assertEquals(['1' => '1', '2' => '2', '3' => '3'], $result);

    // Verify all expected conditions were applied, regardless of order.
    $this->assertCount(2, $conditions);
    $this->assertContains(['comment_type', $this->getBundle(), NULL], $conditions);
    $this->assertContains(['created', 1000, '>='], $conditions);
  }

  /**
   * Tests getEntityIds() with date range filters.
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
      ->willReturn('comment_type');

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
      ->with('comment')
      ->willReturn($storage);
    $this->entityTypeManager->expects($this->once())
      ->method('getDefinition')
      ->with('comment')
      ->willReturn($entity_type);

    // Mock entity field manager to return 'created' field definition.
    $this->entityFieldManager->expects($this->once())
      ->method('getFieldStorageDefinitions')
      ->with('comment')
      ->willReturn(['created' => $this->createMock(FieldStorageDefinitionInterface::class)]);

    $plugin = $this->createPlugin();
    $result = $plugin->getEntityIds(1000, 2000);

    // Entity queries return associative arrays with entity IDs as keys.
    $this->assertEquals(['2' => '2'], $result);

    // Verify all expected conditions were applied, regardless of order.
    $this->assertCount(3, $conditions);
    $this->assertContains(['comment_type', $this->getBundle(), NULL], $conditions);
    $this->assertContains(['created', 1000, '>='], $conditions);
    $this->assertContains(['created', 2000, '<='], $conditions);
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\social_like\Unit\Plugin\BackfillHandler;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\social_like\Plugin\BackfillHandler\LikeBackfillHandler;
use Drupal\Tests\UnitTestCase;
use Drupal\votingapi\VoteInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Unit tests for LikeBackfillHandler.
 *
 * @coversDefaultClass \Drupal\social_like\Plugin\BackfillHandler\LikeBackfillHandler
 * @group social_like
 */
final class LikeBackfillHandlerTest extends UnitTestCase {

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
   * Creates a LikeBackfillHandler plugin instance.
   */
  private function createPlugin(): LikeBackfillHandler {
    $plugin_definition = [
      'id' => 'like',
      'label' => 'Like',
      'entity_type' => 'vote',
      'bundle' => 'like',
      'handler_service' => 'social_like.eda_handler',
      'handler_method' => 'likeCreate',
    ];

    return new LikeBackfillHandler(
      [],
      'like',
      $plugin_definition,
      $this->entityTypeManager,
      $this->entityFieldManager,
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
    $this->assertEquals('like', $plugin->getPluginId());
  }

  /**
   * Tests process() calls likeCreate on EdaHandler.
   *
   * @covers ::process
   */
  public function testProcess(): void {
    $vote = $this->createMock(VoteInterface::class);
    // EdaHandler is final, so we create a test double that implements
    // the method.
    $eda_handler = new class() {
      /**
       * Whether likeCreate was called.
       */
      public bool $likeCreateCalled = FALSE;

      /**
       * The vote passed to likeCreate.
       */
      public ?VoteInterface $votePassed = NULL;

      /**
       * Test handler method.
       */
      public function likeCreate(VoteInterface $vote): void {
        $this->likeCreateCalled = TRUE;
        $this->votePassed = $vote;
      }

    };

    $this->container->expects($this->once())
      ->method('get')
      ->with('social_like.eda_handler')
      ->willReturn($eda_handler);

    $plugin = $this->createPlugin();
    $plugin->process($vote);

    $this->assertTrue($eda_handler->likeCreateCalled);
    $this->assertSame($vote, $eda_handler->votePassed);
  }

  /**
   * Tests getEntityIds() queries votes with bundle filtering.
   *
   * Tests that the base class correctly uses 'timestamp' field when 'created'
   * field doesn't exist (Vote entities use 'timestamp' instead of 'created').
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
      ->with('vote')
      ->willReturn($storage);
    $this->entityTypeManager->expects($this->once())
      ->method('getDefinition')
      ->with('vote')
      ->willReturn($entity_type);

    // Mock entity field manager to return 'timestamp' field definition.
    $this->entityFieldManager->expects($this->once())
      ->method('getFieldStorageDefinitions')
      ->with('vote')
      ->willReturn(['timestamp' => $this->createMock(FieldStorageDefinitionInterface::class)]);

    $plugin = $this->createPlugin();
    $result = $plugin->getEntityIds(1000, NULL);

    // Entity queries return associative arrays with entity IDs as keys.
    $this->assertEquals(['1' => '1', '2' => '2', '3' => '3'], $result);

    // Verify all expected conditions were applied, regardless of order.
    $this->assertCount(2, $conditions);
    $this->assertContains(['type', 'like', NULL], $conditions);
    $this->assertContains(['timestamp', 1000, '>='], $conditions);
  }

  /**
   * Tests getEntityIds() with date range filters.
   *
   * Tests that the base class correctly uses 'timestamp' field for date
   * filtering when 'created' field doesn't exist.
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
      ->with('vote')
      ->willReturn($storage);
    $this->entityTypeManager->expects($this->once())
      ->method('getDefinition')
      ->with('vote')
      ->willReturn($entity_type);

    // Mock entity field manager to return 'timestamp' field definition.
    // Vote entities use 'timestamp' instead of 'created', so the base class
    // will fall back to 'timestamp' when 'created' is not found.
    $this->entityFieldManager->expects($this->once())
      ->method('getFieldStorageDefinitions')
      ->with('vote')
      ->willReturn(['timestamp' => $this->createMock(FieldStorageDefinitionInterface::class)]);

    $plugin = $this->createPlugin();
    $result = $plugin->getEntityIds(1000, 2000);

    // Entity queries return associative arrays with entity IDs as keys.
    $this->assertEquals(['2' => '2'], $result);

    // Verify all expected conditions were applied, regardless of order.
    $this->assertCount(3, $conditions);
    $this->assertContains(['type', 'like', NULL], $conditions);
    $this->assertContains(['timestamp', 1000, '>='], $conditions);
    $this->assertContains(['timestamp', 2000, '<='], $conditions);
  }

  /**
   * Tests getEntityIds() without date filters.
   *
   * @covers ::getEntityIds
   */
  public function testGetEntityIdsWithoutDateFilters(): void {
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
    $query->expects($this->once())
      ->method('condition')
      ->with('type', 'like', NULL)
      ->willReturnSelf();
    $query->expects($this->once())
      ->method('execute')
      ->willReturn(['1' => '1', '2' => '2', '3' => '3']);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('getQuery')
      ->willReturn($query);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('vote')
      ->willReturn($storage);
    $this->entityTypeManager->expects($this->once())
      ->method('getDefinition')
      ->with('vote')
      ->willReturn($entity_type);

    $plugin = $this->createPlugin();
    $result = $plugin->getEntityIds();

    // Entity queries return associative arrays with entity IDs as keys.
    $this->assertEquals(['1' => '1', '2' => '2', '3' => '3'], $result);
  }

}

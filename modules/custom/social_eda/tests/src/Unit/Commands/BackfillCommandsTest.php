<?php

declare(strict_types=1);

namespace Drupal\Tests\social_eda\Unit\Commands;

use Drupal\advancedqueue\Entity\QueueInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\social_eda\Commands\BackfillCommands;
use Drupal\social_eda\Plugin\BackfillHandlerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Unit tests for BackfillCommands.
 *
 * @coversDefaultClass \Drupal\social_eda\Commands\BackfillCommands
 * @group social_eda
 */
final class BackfillCommandsTest extends UnitTestCase {

  /**
   * The backfill handler manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $backfillHandlerManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The output interface.
   *
   * @var \Symfony\Component\Console\Output\OutputInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $output;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->backfillHandlerManager = $this->createMock(PluginManagerInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->output = $this->createMock(OutputInterface::class);
  }

  /**
   * Creates a test command instance.
   */
  protected function createCommand(): BackfillCommands {
    $command = $this->getMockBuilder(BackfillCommands::class)
      ->setConstructorArgs([$this->backfillHandlerManager, $this->entityTypeManager])
      ->onlyMethods(['output'])
      ->getMock();

    $command->expects($this->any())
      ->method('output')
      ->willReturn($this->output);

    return $command;
  }

  /**
   * Tests listPlugins() with no plugins.
   *
   * @covers ::listPlugins
   */
  public function testListPluginsEmpty(): void {
    $this->backfillHandlerManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn([]);

    $this->output->expects($this->once())
      ->method('writeln')
      ->with('No backfill handler plugins found.');

    $command = $this->createCommand();
    $command->listPlugins();
  }

  /**
   * Tests listPlugins() with plugins.
   *
   * @covers ::listPlugins
   */
  public function testListPlugins(): void {
    $definitions = [
      'topic' => [
        'label' => 'Topic',
        'entity_type' => 'node',
        'bundle' => 'topic',
      ],
      'post' => [
        'label' => 'Post',
        'entity_type' => 'post',
        'bundle' => 'post',
      ],
    ];

    $this->backfillHandlerManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $call_count = 0;
    $this->output->expects($this->exactly(4))
      ->method('writeln')
      ->willReturnCallback(function ($message) use (&$call_count) {
        $call_count++;
        if ($call_count === 1) {
          $this->assertEquals('Available backfill handler plugins:', $message);
        }
        elseif ($call_count === 2) {
          $this->assertEquals('', $message);
        }
        elseif ($call_count === 3) {
          $this->assertStringContainsString('topic', $message);
        }
        elseif ($call_count === 4) {
          $this->assertStringContainsString('post', $message);
        }
      });

    $command = $this->createCommand();
    $command->listPlugins();
  }

  /**
   * Tests backfill() with no plugins.
   *
   * @covers ::backfill
   */
  public function testBackfillNoPlugins(): void {
    $this->backfillHandlerManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn([]);

    $this->output->expects($this->once())
      ->method('writeln')
      ->with($this->stringContains('No backfill handler plugins found'));

    $command = $this->createCommand();
    $command->backfill('all');
  }

  /**
   * Tests backfill() with unknown plugin.
   *
   * @covers ::backfill
   */
  public function testBackfillUnknownPlugin(): void {
    $definitions = [
      'topic' => [
        'label' => 'Topic',
        'entity_type' => 'node',
        'bundle' => 'topic',
      ],
    ];

    $this->backfillHandlerManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $call_count = 0;
    $this->output->expects($this->exactly(2))
      ->method('writeln')
      ->willReturnCallback(function ($message) use (&$call_count) {
        $call_count++;
        if ($call_count === 1) {
          $this->assertStringContainsString('Unknown backfill handler plugin', $message);
        }
        elseif ($call_count === 2) {
          $this->assertStringContainsString('social-eda:backfill-list', $message);
        }
      });

    $command = $this->createCommand();
    $command->backfill('unknown');
  }

  /**
   * Tests backfill() with dry-run and no entities.
   *
   * @covers ::backfill
   */
  public function testBackfillDryRunNoEntities(): void {
    $definitions = [
      'topic' => [
        'label' => 'Topic',
        'entity_type' => 'node',
        'bundle' => 'topic',
      ],
    ];

    $handler = $this->createMock(BackfillHandlerInterface::class);
    $handler->expects($this->once())
      ->method('getEntityIds')
      ->with(NULL, NULL)
      ->willReturn([]);

    $this->backfillHandlerManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $this->backfillHandlerManager->expects($this->once())
      ->method('createInstance')
      ->with('topic')
      ->willReturn($handler);

    $this->output->expects($this->atLeast(3))
      ->method('writeln')
      ->with($this->logicalOr(
        $this->stringContains('DRY RUN'),
        $this->equalTo(''),
        $this->stringContains('Processing'),
        $this->stringContains('No entities found'),
        $this->stringContains('Would have queued 0 jobs')
      ));

    $command = $this->createCommand();
    $command->backfill('topic', ['dry-run' => TRUE]);
  }

  /**
   * Tests backfill() with dry-run and entities.
   *
   * @covers ::backfill
   */
  public function testBackfillDryRunWithEntities(): void {
    $definitions = [
      'topic' => [
        'label' => 'Topic',
        'entity_type' => 'node',
        'bundle' => 'topic',
      ],
    ];

    $handler = $this->createMock(BackfillHandlerInterface::class);
    $handler->expects($this->once())
      ->method('getEntityIds')
      ->with(NULL, NULL)
      ->willReturn(['1', '2', '3']);

    $this->backfillHandlerManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $this->backfillHandlerManager->expects($this->once())
      ->method('createInstance')
      ->with('topic')
      ->willReturn($handler);

    $this->output->expects($this->atLeastOnce())
      ->method('writeln')
      ->with($this->callback(function ($message) {
        $message_lower = strtolower($message);
        return $message === '' ||
          strpos($message_lower, 'dry run') !== FALSE ||
          strpos($message_lower, 'processing') !== FALSE ||
          strpos($message_lower, 'found 3 entities') !== FALSE ||
          strpos($message_lower, 'would have queued 3 jobs') !== FALSE;
      }));

    $command = $this->createCommand();
    $command->backfill('topic', ['dry-run' => TRUE]);
  }

  /**
   * Tests backfill() with date filters.
   *
   * @covers ::backfill
   * @covers ::parseDate
   */
  public function testBackfillWithDateFilters(): void {
    $definitions = [
      'topic' => [
        'label' => 'Topic',
        'entity_type' => 'node',
        'bundle' => 'topic',
      ],
    ];

    $handler = $this->createMock(BackfillHandlerInterface::class);
    $handler->expects($this->once())
      ->method('getEntityIds')
      ->with($this->isType('int'), $this->isType('int'))
      ->willReturn([]);

    $this->backfillHandlerManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $this->backfillHandlerManager->expects($this->once())
      ->method('createInstance')
      ->with('topic')
      ->willReturn($handler);

    $this->output->expects($this->atLeastOnce())
      ->method('writeln');

    $command = $this->createCommand();
    $command->backfill('topic', [
      'dry-run' => TRUE,
      'from' => '2023-01-01',
      'to' => '2023-12-31',
    ]);
  }

  /**
   * Tests backfill() with invalid date format aborts command.
   *
   * @covers ::backfill
   * @covers ::parseDate
   */
  public function testBackfillInvalidDate(): void {
    $definitions = [
      'topic' => [
        'label' => 'Topic',
        'entity_type' => 'node',
        'bundle' => 'topic',
      ],
    ];

    $this->backfillHandlerManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    // Command should abort before creating handler instances.
    $this->backfillHandlerManager->expects($this->never())
      ->method('createInstance');

    $this->output->expects($this->once())
      ->method('writeln')
      ->with($this->stringContains('Invalid date format'));

    $command = $this->createCommand();
    $command->backfill('topic', [
      'dry-run' => TRUE,
      'from' => 'invalid-date',
    ]);
  }

  /**
   * Tests backfill() with queue not found.
   *
   * @covers ::backfill
   */
  public function testBackfillQueueNotFound(): void {
    $definitions = [
      'topic' => [
        'label' => 'Topic',
        'entity_type' => 'node',
        'bundle' => 'topic',
      ],
    ];

    $this->backfillHandlerManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    // Command should return early when queue is not found, before creating
    // handlers.
    $this->backfillHandlerManager->expects($this->never())
      ->method('createInstance');

    $queue_storage = $this->createMock(EntityStorageInterface::class);
    $queue_storage->expects($this->once())
      ->method('load')
      ->with('social_eda_backfill')
      ->willReturn(NULL);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('advancedqueue_queue')
      ->willReturn($queue_storage);

    $this->output->expects($this->once())
      ->method('writeln')
      ->with($this->stringContains('Queue "social_eda_backfill" not found'));

    $command = $this->createCommand();
    $command->backfill('topic', ['dry-run' => FALSE]);
  }

  /**
   * Tests backfill() with 'all' type.
   *
   * @covers ::backfill
   */
  public function testBackfillAll(): void {
    $definitions = [
      'topic' => [
        'label' => 'Topic',
        'entity_type' => 'node',
        'bundle' => 'topic',
      ],
      'post' => [
        'label' => 'Post',
        'entity_type' => 'post',
        'bundle' => 'post',
      ],
    ];

    $handler1 = $this->createMock(BackfillHandlerInterface::class);
    $handler1->expects($this->once())
      ->method('getEntityIds')
      ->willReturn(['1']);

    $handler2 = $this->createMock(BackfillHandlerInterface::class);
    $handler2->expects($this->once())
      ->method('getEntityIds')
      ->willReturn(['2']);

    $this->backfillHandlerManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $this->backfillHandlerManager->expects($this->exactly(2))
      ->method('createInstance')
      ->willReturnCallback(function ($plugin_id) use ($handler1, $handler2) {
        if ($plugin_id === 'topic') {
          return $handler1;
        }
        if ($plugin_id === 'post') {
          return $handler2;
        }
        throw new \RuntimeException('Unexpected plugin ID: ' . $plugin_id);
      });

    $this->output->expects($this->atLeastOnce())
      ->method('writeln')
      ->with($this->callback(function ($message) {
        $message_lower = strtolower($message);
        return $message === '' ||
          strpos($message_lower, 'dry run') !== FALSE ||
          strpos($message_lower, 'processing') !== FALSE ||
          strpos($message_lower, 'found') !== FALSE ||
          strpos($message_lower, 'would have queued') !== FALSE ||
          strpos($message_lower, 'no entities') !== FALSE;
      }));

    $command = $this->createCommand();
    $command->backfill('all', ['dry-run' => TRUE]);
  }

  /**
   * Tests parseDate() indirectly through backfill() with valid dates.
   *
   * @covers ::parseDate
   */
  public function testParseDateValid(): void {
    $definitions = [
      'topic' => [
        'label' => 'Topic',
        'entity_type' => 'node',
        'bundle' => 'topic',
      ],
    ];

    $handler = $this->createMock(BackfillHandlerInterface::class);
    // Verify that parseDate converts dates to timestamps correctly.
    $handler->expects($this->once())
      ->method('getEntityIds')
      ->with($this->isType('int'), $this->isType('int'))
      ->willReturnCallback(function ($from, $to) {
        // Verify dates are parsed correctly (timestamps, not NULL).
        $this->assertIsInt($from);
        $this->assertIsInt($to);
        // Verify 'from' is start of day (2023-01-01 00:00:00 UTC).
        $expected_from = (new \DateTime('2023-01-01 00:00:00', new \DateTimeZone('UTC')))->getTimestamp();
        $this->assertEquals($expected_from, $from);
        // Verify 'to' is end of day (2023-12-31 23:59:59 UTC).
        $expected_to = (new \DateTime('2023-12-31 23:59:59', new \DateTimeZone('UTC')))->getTimestamp();
        $this->assertEquals($expected_to, $to);
        return [];
      });

    $this->backfillHandlerManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $this->backfillHandlerManager->expects($this->once())
      ->method('createInstance')
      ->with('topic')
      ->willReturn($handler);

    $this->output->expects($this->atLeastOnce())
      ->method('writeln');

    $command = $this->createCommand();
    $command->backfill('topic', [
      'dry-run' => TRUE,
      'from' => '2023-01-01',
      'to' => '2023-12-31',
    ]);
  }

  /**
   * Tests parseDate() indirectly through backfill() with NULL dates.
   *
   * @covers ::parseDate
   */
  public function testParseDateNull(): void {
    $definitions = [
      'topic' => [
        'label' => 'Topic',
        'entity_type' => 'node',
        'bundle' => 'topic',
      ],
    ];

    $handler = $this->createMock(BackfillHandlerInterface::class);
    $handler->expects($this->once())
      ->method('getEntityIds')
      ->with(NULL, NULL)
      ->willReturn([]);

    $this->backfillHandlerManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $this->backfillHandlerManager->expects($this->once())
      ->method('createInstance')
      ->with('topic')
      ->willReturn($handler);

    $this->output->expects($this->atLeastOnce())
      ->method('writeln');

    $command = $this->createCommand();
    $command->backfill('topic', [
      'dry-run' => TRUE,
      'from' => NULL,
      'to' => NULL,
    ]);
  }

  /**
   * Tests backfill() with batch size option.
   *
   * @covers ::backfill
   */
  public function testBackfillWithBatchSize(): void {
    $definitions = [
      'topic' => [
        'label' => 'Topic',
        'entity_type' => 'node',
        'bundle' => 'topic',
      ],
    ];

    // Create 150 entity IDs to test batching.
    $entity_ids = array_map('strval', range(1, 150));

    $handler = $this->createMock(BackfillHandlerInterface::class);
    $handler->expects($this->once())
      ->method('getEntityIds')
      ->willReturn($entity_ids);

    $this->backfillHandlerManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $this->backfillHandlerManager->expects($this->once())
      ->method('createInstance')
      ->with('topic')
      ->willReturn($handler);

    $this->output->expects($this->atLeastOnce())
      ->method('writeln')
      ->with($this->callback(function ($message) {
        $message_lower = strtolower($message);
        return $message === '' ||
          strpos($message_lower, 'dry run') !== FALSE ||
          strpos($message_lower, 'processing') !== FALSE ||
          strpos($message_lower, 'found 150 entities') !== FALSE ||
          strpos($message_lower, 'would have queued 150 jobs') !== FALSE;
      }));

    $command = $this->createCommand();
    $command->backfill('topic', [
      'dry-run' => TRUE,
      'batch-size' => 50,
    ]);
  }

  /**
   * Tests backfill() with actual queue batching.
   *
   * @covers ::backfill
   */
  public function testBackfillWithQueueBatching(): void {
    $definitions = [
      'topic' => [
        'label' => 'Topic',
        'entity_type' => 'node',
        'bundle' => 'topic',
      ],
    ];

    // Create 150 entity IDs to test batching (3 batches of 50).
    $entity_ids = array_map('strval', range(1, 150));

    $handler = $this->createMock(BackfillHandlerInterface::class);
    $handler->expects($this->once())
      ->method('getEntityIds')
      ->willReturn($entity_ids);

    $this->backfillHandlerManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $this->backfillHandlerManager->expects($this->once())
      ->method('createInstance')
      ->with('topic')
      ->willReturn($handler);

    $queue = $this->createMock(QueueInterface::class);
    $queue->expects($this->exactly(3))
      ->method('enqueueJobs')
      ->with($this->callback(function ($jobs) {
        // Verify each batch contains exactly 50 jobs.
        return is_array($jobs) && count($jobs) === 50;
      }));

    $queue_storage = $this->createMock(EntityStorageInterface::class);
    $queue_storage->expects($this->once())
      ->method('load')
      ->with('social_eda_backfill')
      ->willReturn($queue);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('advancedqueue_queue')
      ->willReturn($queue_storage);

    $this->output->expects($this->atLeastOnce())
      ->method('writeln')
      ->with($this->callback(function ($message) {
        $message_lower = strtolower($message);
        return $message === '' ||
          strpos($message_lower, 'processing') !== FALSE ||
          strpos($message_lower, 'found 150 entities') !== FALSE ||
          strpos($message_lower, 'queued') !== FALSE ||
          strpos($message_lower, 'advancedqueue') !== FALSE;
      }));

    $command = $this->createCommand();
    $command->backfill('topic', [
      'dry-run' => FALSE,
      'batch-size' => 50,
    ]);
  }

  /**
   * Tests backfill() with zero batch size.
   *
   * @covers ::backfill
   */
  public function testBackfillWithZeroBatchSize(): void {
    $definitions = [
      'topic' => [
        'label' => 'Topic',
        'entity_type' => 'node',
        'bundle' => 'topic',
      ],
    ];

    $handler = $this->createMock(BackfillHandlerInterface::class);
    $handler->expects($this->once())
      ->method('getEntityIds')
      ->willReturn(['1', '2', '3']);

    $this->backfillHandlerManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $this->backfillHandlerManager->expects($this->once())
      ->method('createInstance')
      ->with('topic')
      ->willReturn($handler);

    $this->output->expects($this->atLeastOnce())
      ->method('writeln')
      ->with($this->callback(function ($message) {
        $message_lower = strtolower($message);
        return $message === '' ||
          strpos($message_lower, 'dry run') !== FALSE ||
          strpos($message_lower, 'processing') !== FALSE ||
          strpos($message_lower, 'found 3 entities') !== FALSE ||
          strpos($message_lower, 'would have queued 3 jobs') !== FALSE;
      }));

    $command = $this->createCommand();
    // Zero batch size should be normalized to 1.
    $command->backfill('topic', [
      'dry-run' => TRUE,
      'batch-size' => 0,
    ]);
  }

  /**
   * Tests backfill() with negative batch size.
   *
   * @covers ::backfill
   */
  public function testBackfillWithNegativeBatchSize(): void {
    $definitions = [
      'topic' => [
        'label' => 'Topic',
        'entity_type' => 'node',
        'bundle' => 'topic',
      ],
    ];

    $handler = $this->createMock(BackfillHandlerInterface::class);
    $handler->expects($this->once())
      ->method('getEntityIds')
      ->willReturn(['1', '2', '3']);

    $this->backfillHandlerManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $this->backfillHandlerManager->expects($this->once())
      ->method('createInstance')
      ->with('topic')
      ->willReturn($handler);

    $this->output->expects($this->atLeastOnce())
      ->method('writeln')
      ->with($this->callback(function ($message) {
        $message_lower = strtolower($message);
        return $message === '' ||
          strpos($message_lower, 'dry run') !== FALSE ||
          strpos($message_lower, 'processing') !== FALSE ||
          strpos($message_lower, 'found 3 entities') !== FALSE ||
          strpos($message_lower, 'would have queued 3 jobs') !== FALSE;
      }));

    $command = $this->createCommand();
    // Negative batch size should be normalized to 1.
    $command->backfill('topic', [
      'dry-run' => TRUE,
      'batch-size' => -10,
    ]);
  }

}

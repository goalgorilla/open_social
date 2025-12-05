<?php

declare(strict_types=1);

namespace Drupal\Tests\social_eda\Unit\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\Job;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\social_eda\Plugin\BackfillHandlerInterface;
use Drupal\social_eda\Plugin\AdvancedQueue\JobType\BackfillJobType;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Unit tests for BackfillJobType.
 *
 * @coversDefaultClass \Drupal\social_eda\Plugin\AdvancedQueue\JobType\BackfillJobType
 * @group social_eda
 */
final class BackfillJobTypeTest extends UnitTestCase {

  /**
   * Tests successful processing of a backfill job.
   *
   * @covers ::process
   */
  public function testProcessSuccess(): void {
    $entity = $this->createMock(EntityInterface::class);
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('load')
      ->with('123')
      ->willReturn($entity);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->once())
      ->method('getStorage')
      ->with('node')
      ->willReturn($storage);

    $handler = $this->createMock(BackfillHandlerInterface::class);
    $handler->expects($this->once())
      ->method('process')
      ->with($entity);

    $handler_manager = $this->createMock(PluginManagerInterface::class);
    $handler_manager->expects($this->once())
      ->method('createInstance')
      ->with('test_plugin')
      ->willReturn($handler);

    $logger = $this->createMock(LoggerChannelInterface::class);
    $logger->expects($this->once())
      ->method('info')
      ->with($this->stringContains('Successfully processed backfill'));

    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory->expects($this->once())
      ->method('get')
      ->with('social_eda')
      ->willReturn($logger);

    $job_type = new BackfillJobType(
      [],
      'social_eda_backfill',
      ['label' => 'Test'],
      $entity_type_manager,
      $handler_manager,
      $logger_factory
    );

    $job = $this->createMock(Job::class);
    $job->expects($this->once())
      ->method('getPayload')
      ->willReturn([
        'plugin_id' => 'test_plugin',
        'entity_type' => 'node',
        'entity_id' => '123',
      ]);

    $result = $job_type->process($job);

    $this->assertEquals(Job::STATE_SUCCESS, $result->getState());
  }

  /**
   * Tests processing when entity is not found.
   *
   * @covers ::process
   */
  public function testProcessEntityNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('load')
      ->with('123')
      ->willReturn(NULL);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->once())
      ->method('getStorage')
      ->with('node')
      ->willReturn($storage);

    $handler_manager = $this->createMock(PluginManagerInterface::class);
    $handler_manager->expects($this->never())
      ->method('createInstance');

    $logger = $this->createMock(LoggerChannelInterface::class);
    $logger->expects($this->once())
      ->method('warning')
      ->with($this->stringContains('Entity not found'));

    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory->expects($this->once())
      ->method('get')
      ->with('social_eda')
      ->willReturn($logger);

    $job_type = new BackfillJobType(
      [],
      'social_eda_backfill',
      ['label' => 'Test'],
      $entity_type_manager,
      $handler_manager,
      $logger_factory
    );

    $job = $this->createMock(Job::class);
    $job->expects($this->once())
      ->method('getPayload')
      ->willReturn([
        'plugin_id' => 'test_plugin',
        'entity_type' => 'node',
        'entity_id' => '123',
      ]);

    $result = $job_type->process($job);

    $this->assertEquals(Job::STATE_SUCCESS, $result->getState());
  }

  /**
   * Tests processing when payload is invalid.
   *
   * @covers ::process
   */
  public function testProcessInvalidPayload(): void {
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->never())
      ->method('getStorage');

    $handler_manager = $this->createMock(PluginManagerInterface::class);
    $handler_manager->expects($this->never())
      ->method('createInstance');

    $logger = $this->createMock(LoggerChannelInterface::class);
    $logger->expects($this->once())
      ->method('error')
      ->with($this->stringContains('Invalid backfill job payload'));

    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory->expects($this->once())
      ->method('get')
      ->with('social_eda')
      ->willReturn($logger);

    $job_type = new BackfillJobType(
      [],
      'social_eda_backfill',
      ['label' => 'Test'],
      $entity_type_manager,
      $handler_manager,
      $logger_factory
    );

    $job = $this->createMock(Job::class);
    $job->expects($this->once())
      ->method('getPayload')
      ->willReturn([
        'plugin_id' => 'test_plugin',
        // Missing entity_type and entity_id.
      ]);

    $result = $job_type->process($job);

    $this->assertEquals(Job::STATE_FAILURE, $result->getState());
  }

  /**
   * Tests processing when handler throws an exception.
   *
   * @covers ::process
   */
  public function testProcessHandlerException(): void {
    $entity = $this->createMock(EntityInterface::class);
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('load')
      ->with('123')
      ->willReturn($entity);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->once())
      ->method('getStorage')
      ->with('node')
      ->willReturn($storage);

    $handler = $this->createMock(BackfillHandlerInterface::class);
    $handler->expects($this->once())
      ->method('process')
      ->with($entity)
      ->willThrowException(new \RuntimeException('Handler error'));

    $handler_manager = $this->createMock(PluginManagerInterface::class);
    $handler_manager->expects($this->once())
      ->method('createInstance')
      ->with('test_plugin')
      ->willReturn($handler);

    $logger = $this->createMock(LoggerChannelInterface::class);
    $logger->expects($this->once())
      ->method('error')
      ->with($this->stringContains('Failed to process backfill job'));

    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory->expects($this->once())
      ->method('get')
      ->with('social_eda')
      ->willReturn($logger);

    $job_type = new BackfillJobType(
      [],
      'social_eda_backfill',
      ['label' => 'Test'],
      $entity_type_manager,
      $handler_manager,
      $logger_factory
    );

    $job = $this->createMock(Job::class);
    $job->expects($this->once())
      ->method('getPayload')
      ->willReturn([
        'plugin_id' => 'test_plugin',
        'entity_type' => 'node',
        'entity_id' => '123',
      ]);

    $result = $job_type->process($job);

    $this->assertEquals(Job::STATE_FAILURE, $result->getState());
  }

  /**
   * Tests processing when handler doesn't implement BackfillHandlerInterface.
   *
   * @covers ::process
   */
  public function testProcessInvalidHandlerType(): void {
    $entity = $this->createMock(EntityInterface::class);
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('load')
      ->with('123')
      ->willReturn($entity);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->once())
      ->method('getStorage')
      ->with('node')
      ->willReturn($storage);

    // Return an object that doesn't implement BackfillHandlerInterface.
    $invalid_handler = new \stdClass();

    $handler_manager = $this->createMock(PluginManagerInterface::class);
    $handler_manager->expects($this->once())
      ->method('createInstance')
      ->with('test_plugin')
      ->willReturn($invalid_handler);

    $logger = $this->createMock(LoggerChannelInterface::class);
    $logger->expects($this->once())
      ->method('error')
      ->with($this->stringContains('Failed to process backfill job'));

    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory->expects($this->once())
      ->method('get')
      ->with('social_eda')
      ->willReturn($logger);

    $job_type = new BackfillJobType(
      [],
      'social_eda_backfill',
      ['label' => 'Test'],
      $entity_type_manager,
      $handler_manager,
      $logger_factory
    );

    $job = $this->createMock(Job::class);
    $job->expects($this->once())
      ->method('getPayload')
      ->willReturn([
        'plugin_id' => 'test_plugin',
        'entity_type' => 'node',
        'entity_id' => '123',
      ]);

    $result = $job_type->process($job);

    $this->assertEquals(Job::STATE_FAILURE, $result->getState());
  }

  /**
   * Tests create() factory method.
   *
   * @covers ::create
   */
  public function testCreate(): void {
    $container = $this->createMock(ContainerInterface::class);
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $handler_manager = $this->createMock(PluginManagerInterface::class);
    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);

    $container->expects($this->exactly(3))
      ->method('get')
      ->willReturnCallback(function (string $service_id) use ($entity_type_manager, $handler_manager, $logger_factory) {
        return match ($service_id) {
          'entity_type.manager' => $entity_type_manager,
          'plugin.manager.social_eda.backfill_handler' => $handler_manager,
          'logger.factory' => $logger_factory,
          default => throw new \RuntimeException(sprintf('Unknown service: %s', $service_id)),
        };
      });

    $job_type = BackfillJobType::create(
      $container,
      [],
      'social_eda_backfill',
      ['label' => 'Test']
    );

    // Verify the job type was created successfully.
    $this->assertEquals('social_eda_backfill', $job_type->getPluginId());
  }

}

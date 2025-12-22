<?php

declare(strict_types=1);

namespace Drupal\Tests\social_eda\Unit\Plugin;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\social_eda\Plugin\BackfillActorAwareInterface;
use Drupal\social_eda\Plugin\BackfillHandlerBase;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Unit tests for BackfillHandlerBase.
 *
 * @coversDefaultClass \Drupal\social_eda\Plugin\BackfillHandlerBase
 * @group social_eda
 */
final class BackfillHandlerBaseTest extends UnitTestCase {

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
   * Creates a test plugin instance.
   */
  private function createPlugin(array $plugin_definition): BackfillHandlerBase {
    return new class(
      [],
      'test_plugin',
      $plugin_definition,
      $this->entityTypeManager,
      $this->entityFieldManager,
      $this->container
    ) extends BackfillHandlerBase {};
  }

  /**
   * Tests process() calls the handler method.
   *
   * @covers ::process
   */
  public function testProcess(): void {
    $plugin_definition = [
      'handler_service' => 'test.handler',
      'handler_method' => 'testMethod',
    ];

    $entity = $this->createMock(EntityInterface::class);
    $handler = new class() {
      /**
       * Whether the handler method was called.
       */
      public bool $called = FALSE;

      /**
       * Test handler method.
       */
      public function testMethod(EntityInterface $entity): void {
        $this->called = TRUE;
      }

    };

    /** @var \PHPUnit\Framework\MockObject\MockObject $container_mock */
    $container_mock = $this->container;
    $container_mock->expects($this->once())
      ->method('get')
      ->with('test.handler')
      ->willReturn($handler);

    $plugin = $this->createPlugin($plugin_definition);
    $plugin->process($entity);

    $this->assertTrue($handler->called);
  }

  /**
   * Tests process() sets actor when handler implements interface.
   *
   * @covers ::process
   * @covers ::getActorFromEntity
   */
  public function testProcessSetsActorWhenHandlerIsActorAware(): void {
    $plugin_definition = [
      'handler_service' => 'test.handler',
      'handler_method' => 'testMethod',
    ];

    $entity = $this->createMock(EntityInterface::class);
    $actor = $this->createMock(UserInterface::class);

    $handler = new class() implements BackfillActorAwareInterface {
      /**
       * Whether the handler method was called.
       */
      public bool $called = FALSE;

      /**
       * The actor that was set.
       */
      public ?UserInterface $setActor = NULL;

      /**
       * {@inheritdoc}
       */
      public function setActor(?UserInterface $user): void {
        $this->setActor = $user;
      }

      /**
       * {@inheritdoc}
       */
      public function getActor(): ?UserInterface {
        return $this->setActor;
      }

      /**
       * Test handler method.
       */
      public function testMethod(EntityInterface $entity): void {
        $this->called = TRUE;
      }

    };

    // Create a plugin that overrides getActorFromEntity to return the actor.
    $plugin = new class(
      [],
      'test_plugin',
      $plugin_definition,
      $this->entityTypeManager,
      $this->entityFieldManager,
      $this->container,
      $actor
    ) extends BackfillHandlerBase {
      /**
       * The actor to return from getActorFromEntity.
       */
      private ?UserInterface $testActor;

      /**
       * Constructor.
       */
      public function __construct(
        array $configuration,
        string $plugin_id,
        array $plugin_definition,
        EntityTypeManagerInterface $entity_type_manager,
        EntityFieldManagerInterface $entity_field_manager,
        ContainerInterface $container,
        ?UserInterface $testActor = NULL,
      ) {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $container);
        $this->testActor = $testActor;
      }

      /**
       * {@inheritdoc}
       */
      protected function getActorFromEntity(EntityInterface $entity): ?UserInterface {
        return $this->testActor;
      }

    };

    /** @var \PHPUnit\Framework\MockObject\MockObject $container_mock */
    $container_mock = $this->container;
    $container_mock->expects($this->once())
      ->method('get')
      ->with('test.handler')
      ->willReturn($handler);

    $plugin->process($entity);

    $this->assertTrue($handler->called);
    $this->assertSame($actor, $handler->setActor);
  }

  /**
   * Tests process() does not set actor when getActorFromEntity returns NULL.
   *
   * @covers ::process
   * @covers ::getActorFromEntity
   */
  public function testProcessDoesNotSetActorWhenActorIsNull(): void {
    $plugin_definition = [
      'handler_service' => 'test.handler',
      'handler_method' => 'testMethod',
    ];

    $entity = $this->createMock(EntityInterface::class);

    $handler = new class() implements BackfillActorAwareInterface {
      /**
       * Whether the handler method was called.
       */
      public bool $called = FALSE;

      /**
       * Whether setActor was called.
       */
      public bool $setActorCalled = FALSE;

      /**
       * {@inheritdoc}
       */
      public function setActor(?UserInterface $user): void {
        $this->setActorCalled = TRUE;
      }

      /**
       * {@inheritdoc}
       */
      public function getActor(): ?UserInterface {
        return NULL;
      }

      /**
       * Test handler method.
       */
      public function testMethod(EntityInterface $entity): void {
        $this->called = TRUE;
      }

    };

    /** @var \PHPUnit\Framework\MockObject\MockObject $container_mock */
    $container_mock = $this->container;
    $container_mock->expects($this->once())
      ->method('get')
      ->with('test.handler')
      ->willReturn($handler);

    // Default implementation returns NULL, so setActor should not be called.
    $plugin = $this->createPlugin($plugin_definition);
    $plugin->process($entity);

    $this->assertTrue($handler->called);
    $this->assertFalse($handler->setActorCalled);
  }

  /**
   * Tests process() does not set actor when handler is not actor aware.
   *
   * @covers ::process
   */
  public function testProcessDoesNotSetActorWhenHandlerIsNotActorAware(): void {
    $plugin_definition = [
      'handler_service' => 'test.handler',
      'handler_method' => 'testMethod',
    ];

    $entity = $this->createMock(EntityInterface::class);
    $handler = new class() {
      /**
       * Whether the handler method was called.
       */
      public bool $called = FALSE;

      /**
       * Test handler method.
       */
      public function testMethod(EntityInterface $entity): void {
        $this->called = TRUE;
      }

    };

    /** @var \PHPUnit\Framework\MockObject\MockObject $container_mock */
    $container_mock = $this->container;
    $container_mock->expects($this->once())
      ->method('get')
      ->with('test.handler')
      ->willReturn($handler);

    // Create a plugin that overrides getActorFromEntity to return an actor,
    // but handler doesn't implement BackfillActorAwareInterface.
    $actor = $this->createMock(UserInterface::class);
    $plugin = new class(
      [],
      'test_plugin',
      $plugin_definition,
      $this->entityTypeManager,
      $this->entityFieldManager,
      $this->container,
      $actor
    ) extends BackfillHandlerBase {
      /**
       * The actor to return from getActorFromEntity.
       */
      private ?UserInterface $testActor;

      /**
       * Constructor.
       */
      public function __construct(
        array $configuration,
        string $plugin_id,
        array $plugin_definition,
        EntityTypeManagerInterface $entity_type_manager,
        EntityFieldManagerInterface $entity_field_manager,
        ContainerInterface $container,
        ?UserInterface $testActor = NULL,
      ) {
        parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $container);
        $this->testActor = $testActor;
      }

      /**
       * {@inheritdoc}
       */
      protected function getActorFromEntity(EntityInterface $entity): ?UserInterface {
        return $this->testActor;
      }

    };

    $plugin->process($entity);

    $this->assertTrue($handler->called);
    // Handler doesn't implement BackfillActorAwareInterface, so setActor
    // should not be called (and wouldn't exist anyway).
  }

  /**
   * Tests process() throws exception when handler method doesn't exist.
   *
   * @covers ::process
   */
  public function testProcessHandlerMethodNotFound(): void {
    $plugin_definition = [
      'handler_service' => 'test.handler',
      'handler_method' => 'nonExistentMethod',
    ];

    $entity = $this->createMock(EntityInterface::class);
    $handler = new \stdClass();

    /** @var \PHPUnit\Framework\MockObject\MockObject $container_mock */
    $container_mock = $this->container;
    $container_mock->expects($this->once())
      ->method('get')
      ->with('test.handler')
      ->willReturn($handler);

    $plugin = $this->createPlugin($plugin_definition);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Handler service "test.handler" (class: stdClass) does not have callable method "nonExistentMethod"');

    $plugin->process($entity);
  }

  /**
   * Tests process() throws exception when handler_service is missing.
   *
   * @covers ::process
   */
  public function testProcessMissingHandlerService(): void {
    $plugin_definition = [
      'handler_method' => 'testMethod',
    ];

    $plugin = $this->createPlugin($plugin_definition);
    $entity = $this->createMock(EntityInterface::class);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Plugin definition must contain "handler_service" key');

    $plugin->process($entity);
  }

  /**
   * Tests process() throws exception when handler_method is missing.
   *
   * @covers ::process
   */
  public function testProcessMissingHandlerMethod(): void {
    $plugin_definition = [
      'handler_service' => 'test.handler',
    ];

    $plugin = $this->createPlugin($plugin_definition);
    $entity = $this->createMock(EntityInterface::class);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Plugin definition must contain "handler_method" key');

    $plugin->process($entity);
  }

  /**
   * Tests create() factory method.
   *
   * @covers ::create
   */
  public function testCreate(): void {
    $plugin_definition = [
      'entity_type' => 'node',
      'bundle' => 'topic',
    ];

    $container = $this->createMock(ContainerInterface::class);
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_field_manager = $this->createMock(EntityFieldManagerInterface::class);

    /** @var \PHPUnit\Framework\MockObject\MockObject $container_mock */
    $container_mock = $container;
    $container_mock->expects($this->exactly(2))
      ->method('get')
      ->willReturnCallback(function (string $service_id) use ($entity_type_manager, $entity_field_manager) {
        return match ($service_id) {
          'entity_type.manager' => $entity_type_manager,
          'entity_field.manager' => $entity_field_manager,
          default => throw new \RuntimeException(sprintf('Unknown service: %s', $service_id)),
        };
      });

    $plugin = BackfillHandlerBaseTestTestPlugin::create(
      $container,
      [],
      'test_plugin',
      $plugin_definition
    );

    // Verify the plugin was created successfully and has correct definition.
    $this->assertEquals('test_plugin', $plugin->getPluginId());
    $definition = $plugin->getPluginDefinition();
    assert(is_array($definition), 'Plugin definition must be an array.');
    $this->assertEquals('node', $definition['entity_type']);
    $this->assertEquals('topic', $definition['bundle']);
  }

  /**
   * Tests getEntityIds() throws exception when entity_type is missing.
   *
   * @covers ::getEntityIds
   */
  public function testGetEntityIdsMissingEntityType(): void {
    $plugin_definition = [
      'bundle' => 'topic',
    ];

    $plugin = $this->createPlugin($plugin_definition);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Plugin definition must contain "entity_type" key');

    $plugin->getEntityIds();
  }

  /**
   * Tests getEntityIds() works when bundle is missing (optional).
   *
   * @covers ::getEntityIds
   */
  public function testGetEntityIdsMissingBundle(): void {
    $plugin_definition = [
      'entity_type' => 'node',
      // Bundle is optional, so we can omit it.
    ];

    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('accessCheck')
      ->with(FALSE)
      ->willReturnSelf();
    // No bundle condition should be added when bundle is missing.
    $query->expects($this->never())
      ->method('condition');
    $query->expects($this->once())
      ->method('execute')
      ->willReturn(['1' => '1']);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('getQuery')
      ->willReturn($query);

    $entity_type_definition = $this->createMock(EntityTypeInterface::class);
    $entity_type_definition->expects($this->never())
      ->method('hasKey');

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('node')
      ->willReturn($storage);
    $this->entityTypeManager->expects($this->never())
      ->method('getDefinition');

    $plugin = $this->createPlugin($plugin_definition);
    $result = $plugin->getEntityIds();

    $this->assertEquals(['1' => '1'], $result);
  }

  /**
   * Tests getEntityIds() with entity type that has bundles.
   *
   * @covers ::getEntityIds
   */
  public function testGetEntityIdsWithBundle(): void {
    $plugin_definition = [
      'entity_type' => 'node',
      'bundle' => 'topic',
    ];

    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('accessCheck')
      ->with(FALSE)
      ->willReturnSelf();
    $query->expects($this->once())
      ->method('condition')
      ->with('type', 'topic')
      ->willReturnSelf();
    $query->expects($this->once())
      ->method('execute')
      ->willReturn(['1', '2', '3']);

    $entity_type_definition = $this->createMock(EntityTypeInterface::class);
    $entity_type_definition->expects($this->once())
      ->method('hasKey')
      ->with('bundle')
      ->willReturn(TRUE);
    $entity_type_definition->expects($this->once())
      ->method('getKey')
      ->with('bundle')
      ->willReturn('type');

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
      ->willReturn($entity_type_definition);

    $plugin = $this->createPlugin($plugin_definition);
    $result = $plugin->getEntityIds();

    $this->assertEquals(['1', '2', '3'], $result);
  }

  /**
   * Tests getEntityIds() with entity type that has different bundle key.
   *
   * @covers ::getEntityIds
   */
  public function testGetEntityIdsWithDifferentBundleKey(): void {
    $plugin_definition = [
      'entity_type' => 'taxonomy_term',
      'bundle' => 'tags',
    ];

    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('accessCheck')
      ->with(FALSE)
      ->willReturnSelf();
    $query->expects($this->once())
      ->method('condition')
      ->with('vid', 'tags')
      ->willReturnSelf();
    $query->expects($this->once())
      ->method('execute')
      ->willReturn(['5', '6']);

    $entity_type_definition = $this->createMock(EntityTypeInterface::class);
    $entity_type_definition->expects($this->once())
      ->method('hasKey')
      ->with('bundle')
      ->willReturn(TRUE);
    $entity_type_definition->expects($this->once())
      ->method('getKey')
      ->with('bundle')
      ->willReturn('vid');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('getQuery')
      ->willReturn($query);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('taxonomy_term')
      ->willReturn($storage);
    $this->entityTypeManager->expects($this->once())
      ->method('getDefinition')
      ->with('taxonomy_term')
      ->willReturn($entity_type_definition);

    $plugin = $this->createPlugin($plugin_definition);
    $result = $plugin->getEntityIds();

    $this->assertEquals(['5', '6'], $result);
  }

  /**
   * Tests getEntityIds() with entity type that has no bundles.
   *
   * @covers ::getEntityIds
   */
  public function testGetEntityIdsWithoutBundle(): void {
    $plugin_definition = [
      'entity_type' => 'user',
      'bundle' => 'user',
    ];

    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('accessCheck')
      ->with(FALSE)
      ->willReturnSelf();
    $query->expects($this->never())
      ->method('condition');
    $query->expects($this->once())
      ->method('execute')
      ->willReturn(['10', '11', '12']);

    $entity_type_definition = $this->createMock(EntityTypeInterface::class);
    $entity_type_definition->expects($this->once())
      ->method('hasKey')
      ->with('bundle')
      ->willReturn(FALSE);
    $entity_type_definition->expects($this->never())
      ->method('getKey');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('getQuery')
      ->willReturn($query);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('user')
      ->willReturn($storage);
    $this->entityTypeManager->expects($this->once())
      ->method('getDefinition')
      ->with('user')
      ->willReturn($entity_type_definition);

    $plugin = $this->createPlugin($plugin_definition);
    $result = $plugin->getEntityIds();

    $this->assertEquals(['10', '11', '12'], $result);
  }

  /**
   * Tests getEntityIds() with $from parameter.
   *
   * @covers ::getEntityIds
   */
  public function testGetEntityIdsWithFrom(): void {
    $plugin_definition = [
      'entity_type' => 'node',
      'bundle' => 'topic',
    ];

    // 2021-01-01 00:00:00
    $from_timestamp = 1609459200;

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
      ->willReturn(['20', '21']);

    $entity_type_definition = $this->createMock(EntityTypeInterface::class);
    $entity_type_definition->expects($this->once())
      ->method('hasKey')
      ->with('bundle')
      ->willReturn(TRUE);
    $entity_type_definition->expects($this->once())
      ->method('getKey')
      ->with('bundle')
      ->willReturn('type');

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
      ->willReturn($entity_type_definition);

    // Mock entity field manager to return 'created' field definition.
    $this->entityFieldManager->expects($this->once())
      ->method('getFieldStorageDefinitions')
      ->with('node')
      ->willReturn(['created' => $this->createMock(FieldStorageDefinitionInterface::class)]);

    $plugin = $this->createPlugin($plugin_definition);
    $result = $plugin->getEntityIds($from_timestamp);

    $this->assertEquals(['20', '21'], $result);
    $this->assertCount(2, $conditions);
    $this->assertEquals(['type', 'topic', NULL], $conditions[0]);
    $this->assertEquals(['created', $from_timestamp, '>='], $conditions[1]);
  }

  /**
   * Tests getEntityIds() with $to parameter.
   *
   * @covers ::getEntityIds
   */
  public function testGetEntityIdsWithTo(): void {
    $plugin_definition = [
      'entity_type' => 'node',
      'bundle' => 'topic',
    ];

    // 2022-01-01 00:00:00
    $to_timestamp = 1640995200;

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
      ->willReturn(['30', '31', '32']);

    $entity_type_definition = $this->createMock(EntityTypeInterface::class);
    $entity_type_definition->expects($this->once())
      ->method('hasKey')
      ->with('bundle')
      ->willReturn(TRUE);
    $entity_type_definition->expects($this->once())
      ->method('getKey')
      ->with('bundle')
      ->willReturn('type');

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
      ->willReturn($entity_type_definition);

    // Mock entity field manager to return 'created' field definition.
    $this->entityFieldManager->expects($this->once())
      ->method('getFieldStorageDefinitions')
      ->with('node')
      ->willReturn(['created' => $this->createMock(FieldStorageDefinitionInterface::class)]);

    $plugin = $this->createPlugin($plugin_definition);
    $result = $plugin->getEntityIds(NULL, $to_timestamp);

    $this->assertEquals(['30', '31', '32'], $result);
    $this->assertCount(2, $conditions);
    $this->assertEquals(['type', 'topic', NULL], $conditions[0]);
    $this->assertEquals(['created', $to_timestamp, '<='], $conditions[1]);
  }

  /**
   * Tests getEntityIds() with both $from and $to parameters.
   *
   * @covers ::getEntityIds
   */
  public function testGetEntityIdsWithFromAndTo(): void {
    $plugin_definition = [
      'entity_type' => 'node',
      'bundle' => 'topic',
    ];

    // 2021-01-01 00:00:00
    $from_timestamp = 1609459200;
    // 2022-01-01 00:00:00
    $to_timestamp = 1640995200;

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
      ->willReturn(['40', '41', '42', '43']);

    $entity_type_definition = $this->createMock(EntityTypeInterface::class);
    $entity_type_definition->expects($this->once())
      ->method('hasKey')
      ->with('bundle')
      ->willReturn(TRUE);
    $entity_type_definition->expects($this->once())
      ->method('getKey')
      ->with('bundle')
      ->willReturn('type');

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
      ->willReturn($entity_type_definition);

    // Mock entity field manager to return 'created' field definition.
    $this->entityFieldManager->expects($this->once())
      ->method('getFieldStorageDefinitions')
      ->with('node')
      ->willReturn(['created' => $this->createMock(FieldStorageDefinitionInterface::class)]);

    $plugin = $this->createPlugin($plugin_definition);
    $result = $plugin->getEntityIds($from_timestamp, $to_timestamp);

    $this->assertEquals(['40', '41', '42', '43'], $result);
    $this->assertCount(3, $conditions);
    $this->assertEquals(['type', 'topic', NULL], $conditions[0]);
    $this->assertEquals(['created', $from_timestamp, '>='], $conditions[1]);
    $this->assertEquals(['created', $to_timestamp, '<='], $conditions[2]);
  }

  /**
   * Tests getEntityIds() throws exception when entity lacks timestamp fields.
   *
   * @covers ::getEntityIds
   */
  public function testGetEntityIdsMissingCreatedFieldWithDateFilter(): void {
    $plugin_definition = [
      'entity_type' => 'node',
      'bundle' => 'topic',
    ];

    $entity_type_definition = $this->createMock(EntityTypeInterface::class);
    $entity_type_definition->method('hasKey')->willReturn(TRUE);
    $entity_type_definition->method('getKey')->willReturn('type');

    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')->willReturn($storage);
    $this->entityTypeManager->method('getDefinition')->willReturn($entity_type_definition);

    // Mock entity field manager to NOT return 'created' or 'timestamp' fields.
    $this->entityFieldManager->expects($this->once())
      ->method('getFieldStorageDefinitions')
      ->willReturn([]);

    $plugin = $this->createPlugin($plugin_definition);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('does not have a "created" or "timestamp" field');

    $plugin->getEntityIds(1609459200);
  }

  /**
   * Tests getEntityIds() uses 'timestamp' field when 'created' missing.
   *
   * @covers ::getEntityIds
   */
  public function testGetEntityIdsUsesTimestampWhenCreatedMissing(): void {
    $plugin_definition = [
      'entity_type' => 'vote',
      'bundle' => 'like',
    ];

    // 2021-01-01 00:00:00
    $from_timestamp = 1609459200;

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
      ->willReturn(['50', '51']);

    $entity_type_definition = $this->createMock(EntityTypeInterface::class);
    $entity_type_definition->expects($this->once())
      ->method('hasKey')
      ->with('bundle')
      ->willReturn(TRUE);
    $entity_type_definition->expects($this->once())
      ->method('getKey')
      ->with('bundle')
      ->willReturn('type');

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
      ->willReturn($entity_type_definition);

    // Mock entity field manager to return 'timestamp' but NOT 'created' field.
    $this->entityFieldManager->expects($this->once())
      ->method('getFieldStorageDefinitions')
      ->with('vote')
      ->willReturn(['timestamp' => $this->createMock(FieldStorageDefinitionInterface::class)]);

    $plugin = $this->createPlugin($plugin_definition);
    $result = $plugin->getEntityIds($from_timestamp);

    $this->assertEquals(['50', '51'], $result);
    $this->assertCount(2, $conditions);
    $this->assertEquals(['type', 'like', NULL], $conditions[0]);
    $this->assertEquals(['timestamp', $from_timestamp, '>='], $conditions[1]);
  }

  /**
   * Tests getEntityIds() prefers 'created' when both fields exist.
   *
   * @covers ::getEntityIds
   */
  public function testGetEntityIdsPrefersCreatedWhenBothExist(): void {
    $plugin_definition = [
      'entity_type' => 'node',
      'bundle' => 'topic',
    ];

    // 2021-01-01 00:00:00
    $from_timestamp = 1609459200;

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
      ->willReturn(['60', '61']);

    $entity_type_definition = $this->createMock(EntityTypeInterface::class);
    $entity_type_definition->expects($this->once())
      ->method('hasKey')
      ->with('bundle')
      ->willReturn(TRUE);
    $entity_type_definition->expects($this->once())
      ->method('getKey')
      ->with('bundle')
      ->willReturn('type');

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
      ->willReturn($entity_type_definition);

    // Mock entity field manager to return both 'created' and 'timestamp'
    // fields. The base class should prefer 'created'.
    $this->entityFieldManager->expects($this->once())
      ->method('getFieldStorageDefinitions')
      ->with('node')
      ->willReturn([
        'created' => $this->createMock(FieldStorageDefinitionInterface::class),
        'timestamp' => $this->createMock(FieldStorageDefinitionInterface::class),
      ]);

    $plugin = $this->createPlugin($plugin_definition);
    $result = $plugin->getEntityIds($from_timestamp);

    $this->assertEquals(['60', '61'], $result);
    $this->assertCount(2, $conditions);
    $this->assertEquals(['type', 'topic', NULL], $conditions[0]);
    // Should use 'created', not 'timestamp', when both exist.
    $this->assertEquals(['created', $from_timestamp, '>='], $conditions[1]);
  }

}

/**
 * Test plugin class for create() test.
 */
final class BackfillHandlerBaseTestTestPlugin extends BackfillHandlerBase {}

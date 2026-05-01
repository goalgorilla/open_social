<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event\Unit\Plugin\BackfillHandler;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_eda\Plugin\BackfillHandlerBase;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base test class for event enrollment request and invite backfill handlers.
 *
 * Provides shared test methods for testing request and invite handlers that
 * differ only by status value and handler method name.
 */
abstract class EventEnrollmentRequestInviteBackfillHandlerTestBase extends UnitTestCase {

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
   * Creates a plugin instance.
   *
   * @return \Drupal\social_eda\Plugin\BackfillHandlerBase
   *   The plugin instance.
   */
  abstract protected function createPlugin(): BackfillHandlerBase;

  /**
   * Gets the expected status value for this handler.
   *
   * @return int
   *   The status value (e.g., REQUEST_PENDING, REQUEST_APPROVED).
   */
  abstract protected function getExpectedStatusValue(): int;

  /**
   * Gets the expected handler method name.
   *
   * @return string
   *   The handler method name (e.g., 'eventRequestToJoin').
   */
  abstract protected function getExpectedHandlerMethodName(): string;

  /**
   * Gets the expected plugin ID.
   *
   * @return string
   *   The plugin ID.
   */
  abstract protected function getExpectedPluginId(): string;

  /**
   * Tests plugin creation.
   */
  public function testCreate(): void {
    $plugin = $this->createPlugin();
    // Verify plugin ID is set correctly.
    $this->assertEquals($this->getExpectedPluginId(), $plugin->getPluginId());
  }

  /**
   * Tests process() calls the handler method on EdaHandler.
   */
  public function testProcess(): void {
    $enrollment = $this->createMock(EventEnrollmentInterface::class);
    $enrollment->method('getOwner')
      ->willReturn($this->createMock(User::class));
    // EdaHandler is final, so we create a test double that implements
    // the method dynamically using __call.
    $method_name = $this->getExpectedHandlerMethodName();
    $eda_handler = new class($method_name) {
      /**
       * Whether the handler method was called.
       */
      public bool $methodCalled = FALSE;

      /**
       * The enrollment passed to the handler method.
       */
      public ?EventEnrollmentInterface $enrollmentPassed = NULL;

      /**
       * The method name to handle.
       */
      private string $methodName;

      /**
       * Constructor.
       */
      public function __construct(string $method_name) {
        $this->methodName = $method_name;
      }

      /**
       * Magic method to handle dynamic method calls.
       */
      public function __call(string $name, array $arguments): void {
        if ($name === $this->methodName && isset($arguments[0])) {
          $this->methodCalled = TRUE;
          $this->enrollmentPassed = $arguments[0];
        }
      }

    };

    $this->container->expects($this->once())
      ->method('get')
      ->with('social_event.eda_event_enrollment_handler')
      ->willReturn($eda_handler);

    $plugin = $this->createPlugin();
    $plugin->process($enrollment);

    $this->assertTrue($eda_handler->methodCalled);
    $this->assertSame($enrollment, $eda_handler->enrollmentPassed);
  }

  /**
   * Tests process() throws exception for non-enrollment entity.
   */
  public function testProcessThrowsExceptionForInvalidEntity(): void {
    $entity = $this->createMock(EntityInterface::class);

    $plugin = $this->createPlugin();
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Expected EventEnrollmentInterface');
    $plugin->process($entity);
  }

  /**
   * Tests getEntityIds() queries event enrollments with correct status.
   */
  public function testGetEntityIds(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('accessCheck')
      ->with(FALSE)
      ->willReturnSelf();
    $expected_status = $this->getExpectedStatusValue();
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

    $entity_type_definition = $this->createMock(EntityTypeInterface::class);
    $entity_type_definition->expects($this->once())
      ->method('hasKey')
      ->with('bundle')
      ->willReturn(TRUE);
    $entity_type_definition->expects($this->once())
      ->method('getKey')
      ->with('bundle')
      ->willReturn('type');

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('event_enrollment')
      ->willReturn($storage);
    $this->entityTypeManager->expects($this->once())
      ->method('getDefinition')
      ->with('event_enrollment')
      ->willReturn($entity_type_definition);

    $plugin = $this->createPlugin();
    $result = $plugin->getEntityIds();

    // Entity queries return associative arrays with entity IDs as keys.
    $this->assertEquals(['1' => '1', '2' => '2', '3' => '3'], $result);

    // Verify all expected conditions were applied, regardless of order.
    $this->assertCount(2, $conditions);
    $this->assertContains(['type', 'event_enrollment', NULL], $conditions, 'bundle condition should use bundle from plugin definition');
    $this->assertContains(['field_request_or_invite_status', $expected_status, NULL], $conditions);
  }

  /**
   * Tests getEntityIds() with date range filters.
   */
  public function testGetEntityIdsWithDateRange(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('accessCheck')
      ->with(FALSE)
      ->willReturnSelf();
    $expected_status = $this->getExpectedStatusValue();
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

    $entity_type_definition = $this->createMock(EntityTypeInterface::class);
    $entity_type_definition->expects($this->once())
      ->method('hasKey')
      ->with('bundle')
      ->willReturn(TRUE);
    $entity_type_definition->expects($this->once())
      ->method('getKey')
      ->with('bundle')
      ->willReturn('type');

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('event_enrollment')
      ->willReturn($storage);
    $this->entityTypeManager->expects($this->once())
      ->method('getDefinition')
      ->with('event_enrollment')
      ->willReturn($entity_type_definition);

    // Mock entity field manager to return 'created' field definition.
    $this->entityFieldManager->expects($this->once())
      ->method('getFieldStorageDefinitions')
      ->with('event_enrollment')
      ->willReturn(['created' => $this->createMock(FieldStorageDefinitionInterface::class)]);

    $plugin = $this->createPlugin();
    $result = $plugin->getEntityIds(1000, 2000);

    // Entity queries return associative arrays with entity IDs as keys.
    $this->assertEquals(['2' => '2'], $result);

    // Verify all expected conditions were applied, regardless of order.
    $this->assertCount(4, $conditions);
    $this->assertContains(['type', 'event_enrollment', NULL], $conditions, 'bundle condition should use bundle from plugin definition');
    $this->assertContains(['field_request_or_invite_status', $expected_status, NULL], $conditions);
    $this->assertContains(['created', 1000, '>='], $conditions);
    $this->assertContains(['created', 2000, '<='], $conditions);
  }

  /**
   * Tests getEntityIds() returns empty array when no results.
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

    $entity_type_definition = $this->createMock(EntityTypeInterface::class);
    $entity_type_definition->expects($this->once())
      ->method('hasKey')
      ->with('bundle')
      ->willReturn(TRUE);
    $entity_type_definition->expects($this->once())
      ->method('getKey')
      ->with('bundle')
      ->willReturn('type');

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('event_enrollment')
      ->willReturn($storage);
    $this->entityTypeManager->expects($this->once())
      ->method('getDefinition')
      ->with('event_enrollment')
      ->willReturn($entity_type_definition);

    $plugin = $this->createPlugin();
    $result = $plugin->getEntityIds();

    // Should return empty array, not FALSE.
    $this->assertEquals([], $result);
  }

  /**
   * Tests process() throws exception when handler method is not callable.
   */
  public function testProcessThrowsExceptionForWrongHandlerType(): void {
    $enrollment = $this->createMock(EventEnrollmentInterface::class);
    $wrong_handler = new \stdClass();

    $this->container->expects($this->once())
      ->method('get')
      ->with('social_event.eda_event_enrollment_handler')
      ->willReturn($wrong_handler);

    $plugin = $this->createPlugin();
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage(sprintf(
      'does not have callable method "%s"',
      $this->getExpectedHandlerMethodName()
    ));
    $plugin->process($enrollment);
  }

}

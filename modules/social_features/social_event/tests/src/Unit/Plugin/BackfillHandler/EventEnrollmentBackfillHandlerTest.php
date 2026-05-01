<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event\Unit\Plugin\BackfillHandler;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_event\Plugin\BackfillHandler\EventEnrollmentBackfillHandler;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Unit tests for EventEnrollmentBackfillHandler.
 *
 * @coversDefaultClass \Drupal\social_event\Plugin\BackfillHandler\EventEnrollmentBackfillHandler
 * @group social_event
 */
final class EventEnrollmentBackfillHandlerTest extends UnitTestCase {

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
   * Creates an EventEnrollmentBackfillHandler plugin instance.
   */
  private function createPlugin(): EventEnrollmentBackfillHandler {
    $plugin_definition = [
      'id' => 'event_enrollment',
      'label' => 'Event Enrollment',
      'entity_type' => 'event_enrollment',
      'bundle' => 'event_enrollment',
      'handler_service' => 'social_event.eda_event_enrollment_handler',
      'handler_method' => 'eventEnrollmentCreate',
    ];

    return new EventEnrollmentBackfillHandler(
      [],
      'event_enrollment',
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
    $this->assertEquals('event_enrollment', $plugin->getPluginId());
  }

  /**
   * Tests process() calls eventEnrollmentCreate on EdaHandler.
   *
   * @covers ::process
   */
  public function testProcess(): void {
    $enrollment = $this->createMock(EventEnrollmentInterface::class);
    $enrollment->method('getOwner')
      ->willReturn($this->createMock(User::class));
    // EdaHandler is final, so we create a test double that implements
    // the method.
    $eda_handler = new class() {
      /**
       * Whether eventEnrollmentCreate was called.
       */
      public bool $eventEnrollmentCreateCalled = FALSE;

      /**
       * The enrollment passed to eventEnrollmentCreate.
       */
      public ?EventEnrollmentInterface $enrollmentPassed = NULL;

      /**
       * Test handler method.
       */
      public function eventEnrollmentCreate(EventEnrollmentInterface $enrollment): void {
        $this->eventEnrollmentCreateCalled = TRUE;
        $this->enrollmentPassed = $enrollment;
      }

    };

    $this->container->expects($this->once())
      ->method('get')
      ->with('social_event.eda_event_enrollment_handler')
      ->willReturn($eda_handler);

    $plugin = $this->createPlugin();
    $plugin->process($enrollment);

    $this->assertTrue($eda_handler->eventEnrollmentCreateCalled);
    $this->assertSame($enrollment, $eda_handler->enrollmentPassed);
  }

  /**
   * Tests process() throws exception for non-enrollment entity.
   *
   * @covers ::process
   */
  public function testProcessThrowsExceptionForInvalidEntity(): void {
    $entity = $this->createMock(EntityInterface::class);

    $plugin = $this->createPlugin();
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Expected EventEnrollmentInterface');
    $plugin->process($entity);
  }

  /**
   * Tests getEntityIds() queries event enrollments with correct filters.
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
    $this->assertContains(['field_enrollment_status', EventEnrollmentInterface::STATUS_ENROLLED, NULL], $conditions);
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
    $this->assertContains(['field_enrollment_status', EventEnrollmentInterface::STATUS_ENROLLED, NULL], $conditions);
    $this->assertContains(['created', 1000, '>='], $conditions);
    $this->assertContains(['created', 2000, '<='], $conditions);
  }

  /**
   * Tests getEntityIds() returns empty array when no results.
   *
   * @covers ::getEntityIds
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
   *
   * @covers ::process
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
    $this->expectExceptionMessage('does not have callable method "eventEnrollmentCreate"');
    $plugin->process($enrollment);
  }

}

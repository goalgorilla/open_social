<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group_invite\Unit\Plugin\BackfillHandler;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\social_eda\Plugin\BackfillHandlerBase;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base test class for group membership invite backfill handlers.
 *
 * Provides shared test methods for testing invite handlers that differ
 * only by status value and handler method name.
 */
abstract class GroupMembershipInviteBackfillHandlerTestBase extends UnitTestCase {

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
   *   The status value (GroupInvitation::INVITATION_PENDING,
   *   GroupInvitation::INVITATION_ACCEPTED, or
   *   GroupInvitation::INVITATION_REJECTED).
   */
  abstract protected function getExpectedStatusValue(): int;

  /**
   * Gets the expected handler method name.
   *
   * @return string
   *   The handler method name (e.g., 'groupMembershipInviteCreate').
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
    $invite = $this->createMock(GroupRelationshipInterface::class);
    $invite->method('getOwner')
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
       * The invite passed to the handler method.
       */
      public ?GroupRelationshipInterface $invitePassed = NULL;

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
          $this->invitePassed = $arguments[0];
        }
      }

    };

    $this->container->expects($this->once())
      ->method('get')
      ->with('social_group_flexible_group.group_membership.eda_handler')
      ->willReturn($eda_handler);

    $plugin = $this->createPlugin();
    $plugin->process($invite);

    $this->assertTrue($eda_handler->methodCalled);
    $this->assertSame($invite, $eda_handler->invitePassed);
  }

  /**
   * Tests process() throws exception for non-relationship entity.
   */
  public function testProcessThrowsExceptionForInvalidEntity(): void {
    $entity = $this->createMock(EntityInterface::class);

    $plugin = $this->createPlugin();
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Expected GroupRelationshipInterface');
    $plugin->process($entity);
  }

  /**
   * Tests getEntityIds() queries group membership invites with correct status.
   */
  public function testGetEntityIds(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('accessCheck')
      ->with(FALSE)
      ->willReturnSelf();
    $expected_status = $this->getExpectedStatusValue();
    $conditions = [];
    $query->expects($this->exactly(3))
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
      ->with('group_content')
      ->willReturn($storage);

    $plugin = $this->createPlugin();
    $result = $plugin->getEntityIds();

    // Entity queries return associative arrays with entity IDs as keys.
    $this->assertEquals(['1' => '1', '2' => '2', '3' => '3'], $result);

    // Verify all expected conditions were applied, regardless of order.
    // Bundle condition should NOT be added since bundle is empty.
    $this->assertCount(3, $conditions);
    $this->assertContains(['plugin_id', 'group_invitation', NULL], $conditions);
    $this->assertContains(['group_type', 'flexible_group', NULL], $conditions);
    $this->assertContains(['invitation_status', $expected_status, NULL], $conditions);
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
    $query->expects($this->exactly(5))
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
      ->with('group_content')
      ->willReturn($storage);

    // Mock entity field manager to return 'created' field definition.
    $this->entityFieldManager->expects($this->once())
      ->method('getFieldStorageDefinitions')
      ->with('group_content')
      ->willReturn(['created' => $this->createMock(FieldStorageDefinitionInterface::class)]);

    $plugin = $this->createPlugin();
    $result = $plugin->getEntityIds(1000, 2000);

    // Entity queries return associative arrays with entity IDs as keys.
    $this->assertEquals(['2' => '2'], $result);

    // Verify all expected conditions were applied, regardless of order.
    // Bundle condition should NOT be added since bundle is empty.
    $this->assertCount(5, $conditions);
    $this->assertContains(['plugin_id', 'group_invitation', NULL], $conditions);
    $this->assertContains(['group_type', 'flexible_group', NULL], $conditions);
    $this->assertContains(['invitation_status', $expected_status, NULL], $conditions);
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
    $query->expects($this->exactly(3))
      ->method('condition')
      ->willReturnSelf();
    $query->expects($this->once())
      ->method('execute')
      ->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('getQuery')
      ->willReturn($query);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('group_content')
      ->willReturn($storage);

    $plugin = $this->createPlugin();
    $result = $plugin->getEntityIds();

    // Should return empty array, not FALSE.
    $this->assertEquals([], $result);
  }

  /**
   * Tests process() throws exception when handler method is not callable.
   */
  public function testProcessThrowsExceptionForWrongHandlerType(): void {
    $invite = $this->createMock(GroupRelationshipInterface::class);
    $wrong_handler = new \stdClass();

    $this->container->expects($this->once())
      ->method('get')
      ->with('social_group_flexible_group.group_membership.eda_handler')
      ->willReturn($wrong_handler);

    $plugin = $this->createPlugin();
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage(sprintf(
      'does not have callable method "%s"',
      $this->getExpectedHandlerMethodName()
    ));
    $plugin->process($invite);
  }

}

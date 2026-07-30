<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event_managers\Unit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Plugin\Group\RelationHandler\AccessControlInterface;
use Drupal\node\NodeInterface;
use Drupal\social_event_managers\Plugin\Group\RelationHandler\EventsGroupContentAccessControl;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for EventsGroupContentAccessControl.
 *
 * @coversDefaultClass \Drupal\social_event_managers\Plugin\Group\RelationHandler\EventsGroupContentAccessControl
 * @group social_event_managers
 */
final class EventsGroupContentAccessControlTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $cache_contexts_manager = $this->createMock(CacheContextsManager::class);
    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);
    $container = new Container();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Parent Group ACL is used when the event has no managers.
   *
   * @covers ::entityAccess
   */
  public function testParentAccessUsedWhenEventManagersFieldEmpty(): void {
    $account = $this->createAccount(5);
    $node = $this->createEventNode(managers_empty: TRUE);

    $parent = $this->createMock(AccessControlInterface::class);
    $parent->expects($this->once())
      ->method('entityAccess')
      ->with($node, 'update', $account, TRUE)
      ->willReturn(AccessResult::allowed());

    $access = new EventsGroupContentAccessControl($parent);
    $result = $access->entityAccess($node, 'update', $account, TRUE);
    assert($result instanceof AccessResult);

    $this->assertTrue($result->isAllowed());
  }

  /**
   * Group manager with update any is allowed even when not an event manager.
   *
   * @covers ::entityAccess
   */
  public function testGroupManagerAllowedWhenNotEventManager(): void {
    $account = $this->createAccount(5);
    $node = $this->createEventNode(
      managers_empty: FALSE,
      manager_ids: [99],
      view_access: TRUE,
      owner_id: 1,
    );

    $parent = $this->createMock(AccessControlInterface::class);
    $parent->expects($this->once())
      ->method('entityAccess')
      ->with($node, 'update', $account, TRUE)
      ->willReturn(AccessResult::allowed());

    $access = new EventsGroupContentAccessControl($parent);
    $result = $access->entityAccess($node, 'update', $account, TRUE);
    assert($result instanceof AccessResult);

    $this->assertTrue($result->isAllowed());
  }

  /**
   * Group manager with delete any is allowed even when not an event manager.
   *
   * @covers ::entityAccess
   */
  public function testGroupManagerAllowedDeleteWhenNotEventManager(): void {
    $account = $this->createAccount(5);
    $node = $this->createEventNode(
      managers_empty: FALSE,
      manager_ids: [99],
      view_access: TRUE,
      owner_id: 1,
    );

    $parent = $this->createMock(AccessControlInterface::class);
    $parent->expects($this->once())
      ->method('entityAccess')
      ->with($node, 'delete', $account, TRUE)
      ->willReturn(AccessResult::allowed());

    $access = new EventsGroupContentAccessControl($parent);
    $result = $access->entityAccess($node, 'delete', $account, TRUE);
    assert($result instanceof AccessResult);

    $this->assertTrue($result->isAllowed());
  }

  /**
   * Event manager is still allowed when parent Group ACL forbids.
   *
   * @covers ::entityAccess
   */
  public function testEventManagerAllowedWhenParentForbids(): void {
    $account = $this->createAccount(5);
    $node = $this->createEventNode(
      managers_empty: FALSE,
      manager_ids: [5],
      view_access: TRUE,
      owner_id: 1,
    );

    $parent = $this->createMock(AccessControlInterface::class);
    $parent->expects($this->once())
      ->method('entityAccess')
      ->with($node, 'update', $account, TRUE)
      ->willReturn(AccessResult::forbidden());

    $access = new EventsGroupContentAccessControl($parent);
    $result = $access->entityAccess($node, 'update', $account, TRUE);
    assert($result instanceof AccessResult);

    $this->assertTrue($result->isAllowed());
  }

  /**
   * Ordinary member remains forbidden when not an event manager.
   *
   * @covers ::entityAccess
   */
  public function testOrdinaryMemberForbiddenWhenNotEventManager(): void {
    $account = $this->createAccount(5);
    $node = $this->createEventNode(
      managers_empty: FALSE,
      manager_ids: [99],
      view_access: TRUE,
      owner_id: 1,
    );

    $parent = $this->createMock(AccessControlInterface::class);
    $parent->expects($this->once())
      ->method('entityAccess')
      ->with($node, 'update', $account, TRUE)
      ->willReturn(AccessResult::forbidden());

    $access = new EventsGroupContentAccessControl($parent);
    $result = $access->entityAccess($node, 'update', $account, TRUE);
    assert($result instanceof AccessResult);

    $this->assertTrue($result->isForbidden());
  }

  /**
   * Creates a mocked account.
   */
  private function createAccount(int $uid): AccountInterface&MockObject {
    $account = $this->createMock(AccountInterface::class);
    $account->method('id')->willReturn($uid);
    $account->method('hasPermission')->willReturn(FALSE);
    return $account;
  }

  /**
   * Creates an event node mock for access checks.
   *
   * @param bool $managers_empty
   *   Whether field_event_managers is empty.
   * @param list<int> $manager_ids
   *   Event manager user IDs.
   * @param bool $view_access
   *   Whether the account can view the node.
   * @param int $owner_id
   *   Node owner user ID.
   */
  private function createEventNode(
    bool $managers_empty = TRUE,
    array $manager_ids = [],
    bool $view_access = FALSE,
    int $owner_id = 1,
  ): NodeInterface&MockObject {
    $managers_field = $this->createMock(FieldItemListInterface::class);
    $managers_field->method('isEmpty')->willReturn($managers_empty);
    $managers_field->method('getValue')->willReturn(array_map(
      static fn(int $id): array => ['target_id' => $id],
      $manager_ids,
    ));

    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn('event');
    $node->method('getType')->willReturn('event');
    $node->method('getOwnerId')->willReturn($owner_id);
    $node->method('access')->with('view', $this->anything())->willReturn($view_access);
    $node->method('get')
      ->with('field_event_managers')
      ->willReturn($managers_field);
    $node->method('getCacheTags')->willReturn(['node:1']);
    $node->method('getCacheContexts')->willReturn([]);
    $node->method('getCacheMaxAge')->willReturn(-1);

    return $node;
  }

}

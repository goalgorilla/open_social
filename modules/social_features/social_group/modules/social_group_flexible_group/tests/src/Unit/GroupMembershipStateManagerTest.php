<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group_flexible_group\Unit;

use Drupal\group\Entity\GroupMembershipInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\user\UserInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\social_group_flexible_group\Service\GroupMembershipStateManager;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Component\Datetime\TimeInterface;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Tests the GroupMembershipStateManager class.
 *
 * @group social_group_flexible_group
 */
class GroupMembershipStateManagerTest extends UnitTestCase {

  /**
   * The state manager under test.
   *
   * @var \Drupal\social_group_flexible_group\Service\GroupMembershipStateManager
   */
  protected GroupMembershipStateManager $stateManager;

  /**
   * The key-value expirable factory service.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $keyValueExpirableFactory;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected ObjectProphecy $time;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->keyValueExpirableFactory = $this->prophesize(KeyValueExpirableFactoryInterface::class);
    $this->time = $this->prophesize(TimeInterface::class);

    $this->stateManager = new GroupMembershipStateManager(
      $this->keyValueExpirableFactory->reveal(),
      $this->time->reveal()
    );
  }

  /**
   * Tests marking request approval in progress.
   */
  public function testMarkRequestApprovalInProgress(): void {
    $group_id = 123;
    $user_id = 456;
    $timestamp = 1234567890;
    $store = $this->prophesize(KeyValueStoreExpirableInterface::class);

    $this->time->getRequestTime()->willReturn($timestamp);
    $this->keyValueExpirableFactory->get('social_group_flexible_group_request_approvals')
      ->willReturn($store->reveal());
    $store->setWithExpire(
      '123:456',
      [
        'group_id' => $group_id,
        'user_id' => $user_id,
        'timestamp' => $timestamp,
      ],
      10
    )->shouldBeCalledOnce();

    $this->stateManager->markRequestApprovalInProgress($group_id, $user_id);
  }

  /**
   * Tests checking membership from request approval.
   */
  public function testIsMembershipFromRequestApproval(): void {
    $membership = $this->createMembershipMock();
    $group = $this->createGroupMock();
    $user = $this->createUserMock();
    $store = $this->prophesize(KeyValueStoreExpirableInterface::class);

    $group_id = 123;
    $user_id = 456;

    $membership->getGroup()->willReturn($group->reveal());
    $group->id()->willReturn($group_id);
    $membership->getEntity()->willReturn($user->reveal());
    $user->id()->willReturn($user_id);

    $this->keyValueExpirableFactory->get('social_group_flexible_group_request_approvals')
      ->willReturn($store->reveal());
    $store->get('123:456')->willReturn([
      'group_id' => $group_id,
      'user_id' => $user_id,
    ]);

    $store->delete('123:456')->shouldBeCalledOnce();

    $result = $this->stateManager->isMembershipFromRequestApproval($membership->reveal());
    $this->assertTrue($result);
  }

  /**
   * Tests checking membership from request approval when not found.
   */
  public function testIsMembershipFromRequestApprovalNotFound(): void {
    $membership = $this->createMembershipMock();
    $group = $this->createGroupMock();
    $user = $this->createUserMock();
    $store = $this->prophesize(KeyValueStoreExpirableInterface::class);

    $group_id = 123;
    $user_id = 456;

    $membership->getGroup()->willReturn($group->reveal());
    $group->id()->willReturn($group_id);
    $membership->getEntity()->willReturn($user->reveal());
    $user->id()->willReturn($user_id);

    $this->keyValueExpirableFactory->get('social_group_flexible_group_request_approvals')
      ->willReturn($store->reveal());
    $store->get('123:456')->willReturn(NULL);

    $result = $this->stateManager->isMembershipFromRequestApproval($membership->reveal());
    $this->assertFalse($result);
  }

  /**
   * Tests that cleanup is no longer needed with TTL.
   */
  public function testNoCleanupNeededWithTtl(): void {
    // With KeyValueExpirable and TTL, manual cleanup is no longer needed.
    // This test verifies that the cleanupExpiredEntries method no longer
    // exists.
    $this->assertFalse(method_exists($this->stateManager, 'cleanupExpiredEntries'));
  }

  /**
   * Creates a mock GroupMembershipInterface.
   */
  protected function createMembershipMock(): ObjectProphecy {
    return $this->prophesize(GroupMembershipInterface::class);
  }

  /**
   * Creates a mock GroupInterface.
   */
  protected function createGroupMock(): ObjectProphecy {
    return $this->prophesize(GroupInterface::class);
  }

  /**
   * Creates a mock UserInterface.
   */
  protected function createUserMock(): ObjectProphecy {
    return $this->prophesize(UserInterface::class);
  }

}

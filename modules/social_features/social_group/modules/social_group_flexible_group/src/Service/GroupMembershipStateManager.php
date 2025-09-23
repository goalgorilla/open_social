<?php

declare(strict_types=1);

namespace Drupal\social_group_flexible_group\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\group\Entity\GroupMembershipInterface;

/**
 * Manages state for group membership operations to prevent duplicate events.
 */
final class GroupMembershipStateManager {

  /**
   * The collection name for request approvals.
   */
  private const COLLECTION_REQUEST_APPROVALS = 'social_group_flexible_group_request_approvals';

  /**
   * The TTL in seconds for expirable entries.
   */
  private const TTL_SECONDS = 10;

  /**
   * Constructs a GroupMembershipStateManager.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $keyValueExpirableFactory
   *   The key-value expirable factory service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    private readonly KeyValueExpirableFactoryInterface $keyValueExpirableFactory,
    private readonly TimeInterface $time,
  ) {}

  /**
   * Marks a request approval as in progress.
   *
   * @param int $group_id
   *   The group ID.
   * @param int $user_id
   *   The user ID.
   */
  public function markRequestApprovalInProgress(int $group_id, int $user_id): void {
    $key = $this->createStateKey($group_id, $user_id);

    $store = $this->keyValueExpirableFactory->get(self::COLLECTION_REQUEST_APPROVALS);
    $store->setWithExpire(
      $key,
      [
        'group_id' => $group_id,
        'user_id' => $user_id,
        'timestamp' => $this->time->getRequestTime(),
      ],
      self::TTL_SECONDS
    );
  }

  /**
   * Checks if a membership was created from a request approval.
   *
   * @param \Drupal\group\Entity\GroupMembershipInterface $membership
   *   The membership entity.
   *
   * @return bool
   *   TRUE if the membership was created from a request approval.
   */
  public function isMembershipFromRequestApproval(GroupMembershipInterface $membership): bool {
    $group = $membership->getGroup();
    $user = $membership->getEntity();
    $key = $this->createStateKey((int) $group->id(), (int) $user->id());

    $store = $this->keyValueExpirableFactory->get(self::COLLECTION_REQUEST_APPROVALS);
    $approval = $store->get($key);

    if ($approval !== NULL) {
      // Entry exists and is not expired, remove it since we've used it.
      $store->delete($key);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Creates a state key from group and user IDs.
   *
   * @param int $group_id
   *   The group ID.
   * @param int $user_id
   *   The user ID.
   *
   * @return string
   *   The state key.
   */
  private function createStateKey(int $group_id, int $user_id): string {
    return "{$group_id}:{$user_id}";
  }

}

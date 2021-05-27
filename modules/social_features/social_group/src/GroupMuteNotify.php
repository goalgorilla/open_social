<?php

namespace Drupal\social_group;

use Drupal\Core\Session\AccountInterface;
use Drupal\flag\FlagServiceInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Class GroupMuteNotify.
 *
 * Helps to work with mute/unmute group notifications.
 *
 * @package Drupal\social_group
 */
class GroupMuteNotify {

  /**
   * Flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * GroupMuteNotify constructor.
   *
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   Flag service.
   */
  public function __construct(
    FlagServiceInterface $flag_service
  ) {
    $this->flagService = $flag_service;
  }

  /**
   * Check if group notifications are muted.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return bool
   *   TRUE if a user muted notifications for a group.
   */
  public function groupNotifyIsMuted(GroupInterface $group, AccountInterface $account): bool {
    $flaggings = $this->flagService->getAllEntityFlaggings($group, $account);

    return !empty($flaggings);
  }

}

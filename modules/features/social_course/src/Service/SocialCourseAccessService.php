<?php

namespace Drupal\social_course\Service;

use Drupal\Core\Session\AccountInterface;

/**
 * Class for function-service related with groups..
 *
 * @package Drupal\social_course\Service
 */
class SocialCourseAccessService {

  /**
   * The current active user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * Constructs SocialCourseAccessService service.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * Check if current user has full-access to groups.
   *
   * @return bool
   *   When has full-access will return TRUE.
   */
  public function hasFullAccess(): bool {
    $user_roles = $this->currentUser->getRoles();
    return (bool) array_intersect(['sitemanager', 'administrator'], $user_roles);
  }

}

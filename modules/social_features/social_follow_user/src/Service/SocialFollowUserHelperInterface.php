<?php

namespace Drupal\social_follow_user\Service;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\flag\FlagServiceInterface;
use Drupal\profile\Entity\Profile;

/**
 * Defines the helper service interface.
 */
interface SocialFollowUserHelperInterface {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current account.
   * @param \Drupal\flag\FlagServiceInterface $flag
   *   The flag service.
   */
  public function __construct(
    AccountProxyInterface $current_user,
    FlagServiceInterface $flag
  );

  /**
   * Check if following is allowed for the profile.
   *
   * @param \Drupal\profile\Entity\Profile $profile
   *   The profile entity object.
   *
   * @return bool
   *   TRUE or FALSE depending upon following is allowed.
   */
  public function isFollowingAllowed(Profile $profile): bool;

  /**
   * Get following status of user.
   *
   * @param \Drupal\profile\Entity\Profile $profile
   *   The profile entity object.
   *
   * @return bool
   *   TRUE if the user following is enabled, FALSE otherwise.
   */
  public function getFollowingStatus(Profile $profile): bool;

}

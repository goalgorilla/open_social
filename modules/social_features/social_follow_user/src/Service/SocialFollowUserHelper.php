<?php

namespace Drupal\social_follow_user\Service;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\flag\FlagServiceInterface;
use Drupal\profile\Entity\Profile;

/**
 * Defines the helper service.
 */
class SocialFollowUserHelper implements SocialFollowUserHelperInterface {

  /**
   * The current user.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The flag service.
   */
  protected FlagServiceInterface $flagService;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    AccountProxyInterface $current_user,
    FlagServiceInterface $flag
  ) {
    $this->currentUser = $current_user;
    $this->flagService = $flag;
  }

  /**
   * {@inheritdoc}
   */
  public function isFollowingAllowed(Profile $profile): bool {
    $following_enabled = TRUE;

    // Check if disabled user following due to privacy settings.
    if (!$this->getFollowingStatus($profile)) {
      $following_enabled = FALSE;

      // Check if user already followed.
      $flag = $this->flagService->getFlagById('follow_user');
      assert($flag !== NULL, "The 'follow_user' flag type does not exist, this indicates a problem with the social_follow_user module installation.");
      // And display only "Unfollow" button because we should leave the ability
      // to unfollow user.
      if ($this->flagService->getFlagging($flag, $profile, $this->currentUser)) {
        $following_enabled = TRUE;
      }
    }
    return $following_enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function getFollowingStatus(Profile $profile): bool {
    // Check if it's unchanged value and set it to the TRUE because for existing
    // users the "allow following" option should be enabled by default.
    return (bool) ($profile->getOwner()->get('field_allow_following')->value ?? TRUE);
  }

}

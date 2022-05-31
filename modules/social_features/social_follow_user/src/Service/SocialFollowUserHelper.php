<?php

namespace Drupal\social_follow_user\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\flag\FlagServiceInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\UserDataInterface;


/**
 * Defines the helper service.
 */
class SocialFollowUserHelper implements SocialFollowUserHelperInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    AccountProxyInterface $currentUser,
    UserDataInterface $user_data,
    FlagServiceInterface $flag,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->currentUser = $currentUser;
    $this->userData = $user_data;
    $this->flagService = $flag;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function isDisabledFollowing(ProfileInterface $profile): bool {
    $disable_following = FALSE;

    // Check if disabled user following due to privacy settings.
    if ($this->userData->get('social_profile_privacy', $profile->get('uid')->target_id, 'disable_following')) {
      $disable_following = TRUE;

      // Check if user already followed.
      $flag = $this->flagService->getFlagById('follow_user');
      // And display only "Unfollow" button.
      if ($this->flagService->getFlagging($flag, $profile, $this->currentUser)){
        $disable_following = FALSE;
      }
    }

    return $disable_following;
  }

}

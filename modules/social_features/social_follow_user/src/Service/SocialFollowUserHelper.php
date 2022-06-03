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
    AccountProxyInterface $current_user,
    UserDataInterface $user_data,
    FlagServiceInterface $flag,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->currentUser = $current_user;
    $this->userData = $user_data;
    $this->flagService = $flag;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function isFollowingEnabled(ProfileInterface $profile): bool {
    $following_enabled = TRUE;

    // Check if disabled user following due to privacy settings.
    if (!$this->getFollowingStatus((int) $profile->get('uid')->target_id)) {
      $following_enabled = FALSE;

      // Check if user already followed.
      /** @var \Drupal\flag\FlagInterface $flag */
      $flag = $this->flagService->getFlagById('follow_user');
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
  public function setFollowingStatus(int $uid, $status = TRUE): void {
    $this->userData->set(
      'social_profile_privacy',
      $uid,
      'following_enabled',
      $status
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFollowingStatus(int $uid): bool {
    return $this->userData->get(
      'social_profile_privacy',
      $uid,
      'following_enabled'
    );
  }

}

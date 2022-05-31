<?php

namespace Drupal\social_follow_user\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\flag\FlagServiceInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\UserDataInterface;

/**
 * Defines the helper service interface.
 */
interface SocialFollowUserHelperInterface {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current account.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   * @param \Drupal\flag\FlagServiceInterface $flag
   *   The flag service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    AccountProxyInterface $currentUser,
    UserDataInterface $user_data,
    FlagServiceInterface $flag,
    EntityTypeManagerInterface $entityTypeManager
  );

  /**
   * Check if following is disabled for the profile.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile entity object.
   * @return bool
   *   TRUE if the user following is disabled, FALSE otherwise.
   */
  public function isDisabledFollowing(ProfileInterface $profile): bool;

}

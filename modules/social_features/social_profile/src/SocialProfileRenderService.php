<?php

namespace Drupal\social_profile;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Class SocialProfileRenderService.
 *
 * @package Drupal\social_profile
 */
class SocialProfileRenderService {

  /**
   * The Profile Entity Storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $profileStorage;

  /**
   * Cached profiles.
   *
   * @var \Drupal\user\UserInterface[]|\Drupal\Core\Session\AccountInterface[]
   */
  protected $userProfileCache = [];

  /**
   * The Profile View Builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $profileViewBuilder;

  /**
   * SocialProfileRenderService Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->profileStorage = $entity_type_manager->getStorage('profile');
    $this->profileViewBuilder = $entity_type_manager->getViewBuilder('profile');
  }

  /**
   * Renders the profile of a user
   *
   * @param \Drupal\user\UserInterface|\Drupal\Core\Session\AccountInterface $account
   *   The user account for which we render the profile.
   * @param $view_mode
   *   The view mode in which the profile gets rendered.
   *
   * @return array
   *   A render array for the profile.
   */
  public function renderUserProfile($account, $view_mode) {
    // Build cache ID.
    $cid = $account->id() . ':' . $view_mode;

    // Check if we already have rendered something previously.
    if (!empty($this->userProfileCache[$cid])) {
      return $this->userProfileCache[$cid];
    }

    // When we get an AccountInterface, we load the UserInterface.
    if ($account instanceof AccountInterface) {
      $account = User::load($account->id());
    }

    // Load the profile, we don't have anything cached at this moment.
    $profile = $this->profileStorage->loadByUser($account, 'profile');

    // Check if we have a valid profile.
    if (!empty($profile) && $profile instanceof ProfileInterface) {
      // Cache the loaded profile in the static variable.
      $user_profile = $profile;
    }
    else {
      // Return early, we have no valid profile to work with.
      return [];
    }

    // Render the profile in the required view mode.
    $profile_render = $this->profileViewBuilder->view($user_profile, $view_mode);

    // Cache this in the static variable.
    $this->userProfileCache[$cid] = $profile_render;

    // Return the render array.
    return $profile_render;
  }

}

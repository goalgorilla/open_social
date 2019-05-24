<?php

namespace Drupal\social_profile;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\profile\Entity\ProfileInterface;
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
   * @param UserInterface $account
   *   The user account for which we render the profile.
   * @param $view_mode
   *   The view mode in which the profile gets rendered.
   *
   * @return array
   *   A render array for the profile.
   */
  public function renderUserProfile(UserInterface $account, $view_mode) {
    $profiles = &drupal_static(__FUNCTION__);

    // Check if we already have rendered something previously.
    if (!empty($profile[$account->id()]['view_mode'])) {
      return $profile[$account->id()]['view_mode'];
    }

    // Try to load a cached users profile.
    if ($profile[$account->id()]['profile'] instanceof ProfileInterface) {
      $profile = $profile[$account->id()]['profile'];
    }
    else {
      // Load the profile, we don't have anything cached at this moment..
      $profile = $this->profileStorage->loadByUser($account, 'profile');

      // Check if we have a valid profile.
      if ($profile instanceof ProfileInterface) {
        // Cache the loaded profile in the static variable.
        $profile[$account->id()]['profile'] = $profile;
      }
      else {
        // Return early, we have no valid profile to work with.
        return [];
      }
    }

    // Render the profile in the required view mode.
    $profile_render = $this->profileViewBuilder->view($profile, $view_mode);

    // Cache this in the static variable.
    $profiles[$account->id()]['view_mode'] = $profile_render;

    // Return the render array.
    return $profile_render;
  }

}

<?php

namespace Drupal\social_profile_hide_real_names;

use Drupal\Core\Session\AccountInterface;
use Drupal\social_profile_privacy\Service\SocialProfilePrivacyHelperInterface;

/**
 * Defines the helper SocialProfileHideRealNamesHelper class.
 *
 * @package Drupal\social_profile_privacy\Service
 */
class SocialProfileHideRealNamesHelper {

  /**
   * Hide option.
   */
  const HIDE = 0;

  /**
   * Show option.
   */
  const SHOW = 1;

  /**
   * {@inheritdoc}
   */
  public static function getDefaultFieldVisibilityStatus(string $fieldName, AccountInterface $account = NULL) {
    if ($account) {
      /** @var \Drupal\user\UserDataInterface $userData */
      $userData = \Drupal::service('user.data');
      $userStates = $userData->get('social_profile_privacy', $account->id(), 'fields');
    }

    $config = \Drupal::config('social_profile_privacy.settings');
    $globalStates = (array) $config->get('fields');

    $state = $globalStates[$fieldName] ?? SocialProfilePrivacyHelperInterface::SHOW;
    $visibilityStatus = self::SHOW;

    switch ($state) {
      case SocialProfilePrivacyHelperInterface::SHOW:
        $visibilityStatus = self::SHOW;
        break;

      case SocialProfilePrivacyHelperInterface::CONFIGURABLE:
        if (isset($userStates[$fieldName])) {
          $visibilityStatus = $userStates[$fieldName];
        }
        break;

      case SocialProfilePrivacyHelperInterface::HIDE:
        $visibilityStatus = self::HIDE;
        break;
    }

    return $visibilityStatus;
  }

}

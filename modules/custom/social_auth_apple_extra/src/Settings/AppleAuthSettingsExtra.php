<?php

namespace Drupal\social_auth_apple_extra\Settings;

use Drupal\social_auth_apple\Settings\AppleAuthSettings;
use Drupal\social_auth_extra\Settings\SettingsExtraTrait;

/**
 * Returns the client information.
 *
 * @package Drupal\social_auth_apple_extra\Settings
 */
class AppleAuthSettingsExtra extends AppleAuthSettings implements AppleAuthSettingsExtraInterface {

  use SettingsExtraTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSocialNetworkKey() {
    return \Drupal::service('social_auth_apple.user_manager')->getSocialNetworkKey();
  }

}

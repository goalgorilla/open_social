<?php

namespace Drupal\social_auth_extra\Settings;

use Drupal\social_auth\Settings\SettingsInterface;

/**
 * Interface SettingsExtraInterface.
 *
 * @package Drupal\social_auth_extra\Settings
 */
interface SettingsExtraInterface extends SettingsInterface {

  /**
   * Returns status of social network.
   *
   * @return bool
   *   The status of the social network.
   */
  public function isActive();

  /**
   * Returns key-name of a social network.
   *
   * @return string
   *   The key-name of a social network.
   */
  public static function getSocialNetworkKey();

}

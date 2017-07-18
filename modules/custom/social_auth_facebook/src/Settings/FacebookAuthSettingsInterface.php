<?php

namespace Drupal\social_auth_facebook\Settings;

/**
 * Defines an interface for Social Auth Facebook settings.
 */
interface FacebookAuthSettingsInterface {

  /**
   * Gets the app ID.
   *
   * @return string
   *   The app ID.
   */
  public function getAppId();

  /**
   * Gets the app secret.
   *
   * @return string
   *   The app secret.
   */
  public function getAppSecret();

  /**
   * Gets the default graph version.
   *
   * @return string
   *   The app default graph version.
   */
  public function getGraphVersion();

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

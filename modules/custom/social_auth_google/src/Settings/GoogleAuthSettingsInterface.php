<?php

namespace Drupal\social_auth_google\Settings;

/**
 * Defines an interface for Social Auth Google settings.
 */
interface GoogleAuthSettingsInterface {

  /**
   * Gets the client ID.
   *
   * @return string
   *   The client ID.
   */
  public function getClientId();

  /**
   * Gets the client secret.
   *
   * @return string
   *   The client secret.
   */
  public function getClientSecret();

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

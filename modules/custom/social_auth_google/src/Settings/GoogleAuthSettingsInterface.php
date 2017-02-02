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
   */
  public function isActive();

  /**
   * Returns key-name of a social network.
   *
   * @return string
   */
  public static function getSocialNetworkKey();

}
<?php

namespace Drupal\social_auth_linkedin\Settings;

use Drupal\social_auth_extra\Settings\SettingsExtraInterface;

/**
 * Defines an interface for Social Auth LinkedIn settings.
 */
interface LinkedInAuthSettingsInterface extends SettingsExtraInterface {

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
   * Returns key-name of a social network.
   *
   * @return string
   *   The key-name of a social network.
   */
  public static function getSocialNetworkKey();

}

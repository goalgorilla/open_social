<?php

namespace Drupal\social_auth_google\Settings;

use Drupal\social_auth_extra\Settings\SettingsExtraInterface;

/**
 * Defines an interface for Social Auth Google settings.
 */
interface GoogleAuthSettingsInterface extends SettingsExtraInterface {

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

}

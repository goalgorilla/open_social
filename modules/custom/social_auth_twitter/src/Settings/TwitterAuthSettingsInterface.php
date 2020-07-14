<?php

namespace Drupal\social_auth_twitter\Settings;

use Drupal\social_auth_extra\Settings\SettingsExtraInterface;

/**
 * Defines an interface for Social Auth Twitter settings.
 */
interface TwitterAuthSettingsInterface extends SettingsExtraInterface {

  /**
   * Gets the consumer key.
   *
   * @return string
   *   The consumer key.
   */
  public function getConsumerKey();

  /**
   * Gets the consumer secret.
   *
   * @return string
   *   The consumer secret.
   */
  public function getConsumerSecret();

  /**
   * Returns key-name of a social network.
   *
   * @return string
   *   The key-name of a social network.
   */
  public static function getSocialNetworkKey();

}

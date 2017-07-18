<?php

namespace Drupal\social_auth_twitter\Settings;

/**
 * Defines an interface for Social Auth Twitter settings.
 */
interface TwitterAuthSettingsInterface {

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

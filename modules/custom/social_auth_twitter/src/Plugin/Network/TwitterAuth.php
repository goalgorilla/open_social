<?php

namespace Drupal\social_auth_twitter\Plugin\Network;

use Drupal\social_api\SocialApiException;
use Drupal\social_auth_extra\Plugin\Network\NetworkExtraBase;
use Drupal\social_auth_twitter\Settings\TwitterAuthSettings;

/**
 * Defines Social Auth Twitter Network Plugin.
 *
 * @Network(
 *   id = "social_auth_twitter",
 *   social_network = "Twitter",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *        "class": "\Drupal\social_auth_twitter\Settings\TwitterAuthSettings",
 *        "config_id": "social_auth_twitter.settings"
 *     }
 *   }
 * )
 */
class TwitterAuth extends NetworkExtraBase {

  /**
   * {@inheritdoc}
   */
  public function initSdk() {
    $class_name = '\Abraham\TwitterOAuth\TwitterOAuth';

    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The PHP SDK for Twitter Client could not be found. Class: %s.', $class_name));
    }

    /* @var \Drupal\social_auth_twitter\Settings\TwitterAuthSettings $settings */
    $settings = $this->settings;

    if (!$this->validateConfig($settings)) {
      return FALSE;
    }

    // Creates a and sets data to TwitterOAuth object.
    return new $class_name($settings->getConsumerKey(), $settings->getConsumerSecret());
  }

  /**
   * Checks that module is configured.
   *
   * @param \Drupal\social_auth_twitter\Settings\TwitterAuthSettings $settings
   *   The Twitter auth settings.
   *
   * @return bool
   *   True if module is configured, False otherwise.
   */
  protected function validateConfig(TwitterAuthSettings $settings) {
    $consumer_key = $settings->getConsumerKey();
    $consumer_secret = $settings->getConsumerSecret();

    if (!$consumer_key || !$consumer_secret) {
      $this->loggerFactory
        ->get('social_auth_twitter')
        ->error('Define Consumer Key and Consumer Secret on module settings.');

      return FALSE;
    }

    return TRUE;
  }

  /**
   * Returns an instance of storage that handles data.
   *
   * @return object
   *   An instance of the storage that handles the data.
   */
  public function getDataHandler() {
    $data_handler = \Drupal::service('social_auth_extra.session_persistent_data_handler');
    $data_handler->setPrefix('social_auth_twitter_');

    return $data_handler;
  }

}

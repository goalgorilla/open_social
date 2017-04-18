<?php

namespace Drupal\social_auth_google\Plugin\Network;

use Drupal\social_auth\Plugin\Network\SocialAuthNetwork;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth_google\Settings\GoogleAuthSettings;

/**
 * Defines a Network Plugin for Social Auth Google.
 *
 * @package Drupal\social_auth_google\Plugin\Network
 *
 * @Network(
 *   id = "social_auth_google",
 *   social_network = "Google",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth_google\Settings\GoogleAuthSettings",
 *       "config_id": "social_auth_google.settings"
 *     }
 *   }
 * )
 */
class GoogleAuth extends SocialAuthNetwork {

  /**
   * Returns an instance of sdk.
   *
   * @return mixed
   * @throws \Drupal\social_api\SocialApiException
   */
  public function initSdk() {
    $class_name = '\Google_Client';

    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The PHP SDK for Google Services could not be found. Class: %s.', $class_name));
    }

    if (!$this->validateConfig($this->settings)) {
      return FALSE;
    }

    $client = new $class_name();
    $client->setClientId($this->settings->getClientId());
    $client->setClientSecret($this->settings->getClientSecret());

    return $client;
  }

  /**
   * Returns status of social network.
   *
   * @return bool
   */
  public function isActive() {
    return (bool) $this->settings->isActive();
  }

  /**
   * Checks that module is configured.
   *
   * @param \Drupal\social_auth_google\Settings\GoogleAuthSettings $settings
   *   The Google auth settings.
   *
   * @return bool True if module is configured
   *   True if module is configured
   *   False otherwise
   */
  protected function validateConfig(GoogleAuthSettings $settings) {
    $client_id = $settings->getClientId();
    $client_secret = $settings->getClientSecret();

    if (!$client_id || !$client_secret) {
      $this->loggerFactory
        ->get('social_auth_google')
        ->error('Define Client ID and Client Secret on module settings.');

      return FALSE;
    }

    return TRUE;
  }

  /**
   * Returns key-name of a social network.
   *
   * @return string
   */
  public function getSocialNetworkKey() {
    return $this->settings->getSocialNetworkKey();
  }

  /**
   * Returns an instance of storage that handles data.
   *
   * @return object
   */
  public function getDataHandler() {
    $data_handler = \Drupal::service('social_auth_extra.session_persistent_data_handler');
    $data_handler->setPrefix('social_auth_google_');

    return $data_handler;
  }

}

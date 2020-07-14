<?php

namespace Drupal\social_auth_linkedin\Plugin\Network;

use Drupal\social_api\SocialApiException;
use Drupal\social_auth_extra\Plugin\Network\NetworkExtraBase;
use Drupal\social_auth_linkedin\Settings\LinkedInAuthSettings;

/**
 * Defines a Network Plugin for Social Auth LinkedIn.
 *
 * @package Drupal\social_auth_linkedin\Plugin\Network
 *
 * @Network(
 *   id = "social_auth_linkedin",
 *   social_network = "LinkedIn",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth_linkedin\Settings\LinkedInAuthSettings",
 *       "config_id": "social_auth_linkedin.settings"
 *     }
 *   }
 * )
 */
class LinkedInAuth extends NetworkExtraBase {

  /**
   * Returns an instance of sdk.
   *
   * @return mixed
   *   Returns a new LinkedIn instance or FALSE if the config was incorrect.
   *
   * @throws \Drupal\social_api\SocialApiException
   */
  public function initSdk() {
    $class_name = '\LinkedIn\Client';

    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The PHP SDK for LinkedIn could not be found. Class: %s.', $class_name));
    }

    if (!$this->validateConfig($this->settings)) {
      return FALSE;
    }

    return new $class_name($this->settings->getClientId(), $this->settings->getClientSecret());
  }

  /**
   * Checks that module is configured.
   *
   * @param \Drupal\social_auth_linkedin\Settings\LinkedInAuthSettings $settings
   *   The LinkedIn auth settings.
   *
   * @return bool
   *   True if module is configured, False otherwise.
   */
  protected function validateConfig(LinkedInAuthSettings $settings) {
    $client_id = $settings->getClientId();
    $client_secret = $settings->getClientSecret();

    if (!$client_id || !$client_secret) {
      $this->loggerFactory
        ->get('social_auth_linkedin')
        ->error('Define Client ID and Client Secret on module settings.');

      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSocialNetworkKey() {
    return $this->settings->getSocialNetworkKey();
  }

  /**
   * Returns an instance of storage that handles data.
   *
   * @return object
   *   An instance of the storage that handles the data.
   */
  public function getDataHandler() {
    $data_handler = \Drupal::service('social_auth_extra.session_persistent_data_handler');
    $data_handler->setPrefix('social_auth_linkedin_');

    return $data_handler;
  }

}

<?php

namespace Drupal\social_auth_linkedin\Settings;

use Drupal\social_auth_extra\Settings\SettingsExtraBase;

/**
 * Defines methods to get Social Auth LinkedIn app settings.
 */
class LinkedInAuthSettings extends SettingsExtraBase implements LinkedInAuthSettingsInterface {

  /**
   * Client ID.
   *
   * @var string
   */
  protected $clientId;

  /**
   * Client Secret.
   *
   * @var string
   */
  protected $clientSecret;

  /**
   * {@inheritdoc}
   */
  public function getClientId() {
    if (!$this->clientId) {
      $this->clientId = $this->config->get('client_id');
    }

    return $this->clientId;
  }

  /**
   * {@inheritdoc}
   */
  public function getClientSecret() {
    if (!$this->clientSecret) {
      $this->clientSecret = $this->config->get('client_secret');
    }

    return $this->clientSecret;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSocialNetworkKey() {
    return 'linkedin';
  }

}

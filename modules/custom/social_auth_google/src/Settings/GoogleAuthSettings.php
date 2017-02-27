<?php

namespace Drupal\social_auth_google\Settings;

use Drupal\social_api\Settings\SettingsBase;

/**
 * Defines methods to get Social Auth Google app settings.
 */
class GoogleAuthSettings extends SettingsBase implements GoogleAuthSettingsInterface {

  /**
   * Client ID.
   *
   * @var string
   */
  protected $clientId;

  /**
   * Client secret.
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
  public function isActive() {
    return (bool) $this->config->get('status');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSocialNetworkKey() {
    return 'google';
  }

}

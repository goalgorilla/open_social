<?php

namespace Drupal\social_auth_facebook\Settings;

use Drupal\social_api\Settings\SettingsBase;

/**
 * Defines methods to get Social Auth Facebook app settings.
 */
class FacebookAuthSettings extends SettingsBase implements FacebookAuthSettingsInterface {

  /**
   * Facebook Application ID.
   *
   * @var string
   */
  protected $appId;

  /**
   * Facebook Application Secret.
   *
   * @var string
   */
  protected $appSecret;

  /**
   * The default graph version.
   *
   * @var string
   */
  protected $graphVersion;

  /**
   * The default access token.
   *
   * @var string
   */
  protected $defaultToken;

  /**
   * {@inheritdoc}
   */
  public function getAppId() {
    if (!$this->appId) {
      $this->appId = $this->config->get('app_id');
    }

    return $this->appId;
  }

  /**
   * {@inheritdoc}
   */
  public function getAppSecret() {
    if (!$this->appSecret) {
      $this->appSecret = $this->config->get('app_secret');
    }

    return $this->appSecret;
  }

  /**
   * {@inheritdoc}
   */
  public function getGraphVersion() {
    if (!$this->graphVersion) {
      $this->graphVersion = $this->config->get('graph_version');
    }

    return $this->graphVersion;
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
    return 'facebook';
  }

}

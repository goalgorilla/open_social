<?php

namespace Drupal\social_auth_twitter\Settings;

use Drupal\social_api\Settings\SettingsBase;

/**
 * Returns the client information.
 */
class TwitterAuthSettings extends SettingsBase implements TwitterAuthSettingsInterface {

  /**
   * Consumer Key.
   *
   * @var string
   */
  protected $consumerKey;

  /**
   * Consumer secret.
   *
   * @var string
   */
  protected $consumerSecret;

  /**
   * {@inheritdoc}
   */
  public function getConsumerKey() {
    if (!$this->consumerKey) {
      $this->consumerKey = $this->config->get('consumer_key');
    }

    return $this->consumerKey;
  }

  /**
   * {@inheritdoc}
   */
  public function getConsumerSecret() {
    if (!$this->consumerSecret) {
      $this->consumerSecret = $this->config->get('consumer_secret');
    }

    return $this->consumerSecret;
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
    return 'twitter';
  }

}

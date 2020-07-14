<?php

namespace Drupal\social_auth_twitter\Settings;

use Drupal\social_auth_extra\Settings\SettingsExtraBase;

/**
 * Returns the client information.
 */
class TwitterAuthSettings extends SettingsExtraBase implements TwitterAuthSettingsInterface {

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
  public static function getSocialNetworkKey() {
    return 'twitter';
  }

}

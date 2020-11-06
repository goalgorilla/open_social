<?php

namespace Drupal\social_auth_apple_extra\Plugin\Network;

use Drupal\Core\Url;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth_apple\Plugin\Network\AppleAuth;
use Drupal\social_auth_extra\Plugin\Network\NetworkExtraInterface;
use Drupal\social_auth_extra\Plugin\Network\NetworkExtraTrait;

/**
 * Class AppleAuthExtra.
 *
 * @package Drupal\social_auth_apple_extra\Plugin\Network
 */
class AppleAuthExtra extends AppleAuth implements NetworkExtraInterface {

  use NetworkExtraTrait;

  /**
   * {@inheritdoc}
   */
  public function initSdk() {
    $data = drupal_static(NULL);
    $route_name = $data['social_auth_apple_extra'] ?? NULL;

    if (!$route_name) {
      return parent::initSdk();
    }

    $class_name = '\League\OAuth2\Client\Provider\Apple';

    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The Apple library for PHP League OAuth2 not found. Class: %s.', $class_name));
    }

    /** @var \Drupal\social_auth_apple\Settings\AppleAuthSettings $settings */
    $settings = $this->settings;

    if ($this->validateConfig($settings)) {
      // All these settings are mandatory.
      $league_settings = [
        'clientId' => $settings->getClientId(),
        'teamId' => $settings->getTeamId(),
        'keyFileId' => $settings->getKeyFileId(),
        'keyFilePath' => DRUPAL_ROOT . '/../' . $settings->getKeyFilePath(),
        'redirectUri' => Url::fromRoute($route_name)->setAbsolute()->toString(),
      ];

      // Proxy configuration data for outward proxy.
      $proxy_url = $this->siteSettings->get('http_client_config')['proxy']['http'];

      if ($proxy_url) {
        $league_settings['proxy'] = $proxy_url;
      }

      return new $class_name($league_settings);
    }

    return FALSE;
  }

}

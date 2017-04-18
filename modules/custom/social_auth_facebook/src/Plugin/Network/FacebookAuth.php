<?php

namespace Drupal\social_auth_facebook\Plugin\Network;

use Drupal\social_auth\Plugin\Network\SocialAuthNetwork;
use Drupal\social_api\SocialApiException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\social_auth_facebook\Settings\FacebookAuthSettings;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Defines a Network Plugin for Social Auth Facebook.
 *
 * @package Drupal\social_auth_facebook\Plugin\Network
 *
 * @Network(
 *   id = "social_auth_facebook",
 *   social_network = "Facebook",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth_facebook\Settings\FacebookAuthSettings",
 *       "config_id": "social_auth_facebook.settings"
 *     }
 *   }
 * )
 */
class FacebookAuth extends SocialAuthNetwork {

  protected $loggerFactory;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->loggerFactory = $logger_factory;

    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $config_factory);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('logger.factory')
    );
  }

  /**
   * Returns an instance of sdk.
   *
   * @return mixed
   * @throws \Drupal\social_api\SocialApiException
   */
  public function initSdk() {
    $class_name = '\Facebook\Facebook';

    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The PHP SDK for Facebook could not be found. Class: %s.', $class_name));
    }

    if (!$this->validateConfig($this->settings)) {
      return FALSE;
    }

    $settings = [
      'app_id' => $this->settings->getAppId(),
      'app_secret' => $this->settings->getAppSecret(),
      'default_graph_version' => 'v' . $this->settings->getGraphVersion(),
      'persistent_data_handler' => $this->getDataHandler(),
    ];

    return new $class_name($settings);
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
   * @param \Drupal\social_auth_facebook\Settings\FacebookAuthSettings $settings
   *   The Facebook auth settings.
   *
   * @return bool True if module is configured
   *   True if module is configured
   *   False otherwise
   */
  protected function validateConfig(FacebookAuthSettings $settings) {
    $app_id = $settings->getAppId();
    $app_secret = $settings->getAppSecret();
    $graph_version = $settings->getGraphVersion();

    if (!$app_id || !$app_secret || !$graph_version) {
      $this->loggerFactory
        ->get('social_auth_facebook')
        ->error('Define App ID and App Secret on module settings.');

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
    $data_handler = \Drupal::service('social_auth_facebook.persistent_data_handler');
    $data_handler->setPrefix('social_auth_facebook_');

    return $data_handler;
  }

}

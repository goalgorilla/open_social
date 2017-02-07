<?php

namespace Drupal\social_auth_linkedin\Plugin\Network;

use Drupal\social_auth\Plugin\Network\SocialAuthNetwork;
use Drupal\social_api\SocialApiException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\social_auth_linkedin\Settings\LinkedInAuthSettings;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

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
class LinkedInAuth extends SocialAuthNetwork {

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
    $class_name = '\Happyr\LinkedIn\LinkedIn';

    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The PHP SDK for LinkedIn could not be found. Class: %s.', $class_name));
    }

    if (!$this->validateConfig($this->settings)) {
      return FALSE;
    }

    return new $class_name($this->settings->getClientId(), $this->settings->getClientSecret());
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
   * @param \Drupal\social_auth_linkedin\Settings\LinkedInAuthSettings $settings
   *   The LinkedIn auth settings.
   *
   * @return bool True if module is configured
   *   True if module is configured
   *   False otherwise
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
    $data_handler->setPrefix('social_auth_linkedin_');

    return $data_handler;
  }

}

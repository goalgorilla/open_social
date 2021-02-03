<?php

namespace Drupal\social_auth_linkedin\Plugin\Network;

use Drupal\social_auth\Plugin\Network\SocialAuthNetwork;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth_extra\AuthSessionDataHandler;
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

  /**
   * The session handler.
   *
   * @var \Drupal\social_auth_extra\AuthSessionDataHandler
   */
  protected $sessionHandler;

  /**
   * LinkedInAuth constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\social_auth_extra\AuthSessionDataHandler $session_handler
   *   The session handler.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, AuthSessionDataHandler $session_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $config_factory);

    $this->loggerFactory = $logger_factory;
    $this->sessionHandler = $session_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('social_auth_extra.session_persistent_data_handler')
    );
  }

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
   * Returns status of social network.
   *
   * @return bool
   *   The status of the social network.
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
    $this->sessionHandler->setPrefix('social_auth_linkedin_');

    return $this->sessionHandler;
  }

}

<?php

namespace Drupal\social_auth_twitter\Plugin\Network;

use Drupal\social_auth\Plugin\Network\SocialAuthNetwork;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth_extra\AuthSessionDataHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\social_auth_twitter\Settings\TwitterAuthSettings;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Defines Social Auth Twitter Network Plugin.
 *
 * @Network(
 *   id = "social_auth_twitter",
 *   social_network = "Twitter",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *        "class": "\Drupal\social_auth_twitter\Settings\TwitterAuthSettings",
 *        "config_id": "social_auth_twitter.settings"
 *     }
 *   }
 * )
 */
class TwitterAuth extends SocialAuthNetwork {

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The session handler.
   *
   * @var \Drupal\social_auth_extra\AuthSessionDataHandler
   */
  protected $sessionHandler;

  /**
   * Twitter constructor.
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
   * {@inheritdoc}
   */
  public function initSdk() {
    $class_name = '\Abraham\TwitterOAuth\TwitterOAuth';

    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The PHP SDK for Twitter Client could not be found. Class: %s.', $class_name));
    }

    /** @var \Drupal\social_auth_twitter\Settings\TwitterAuthSettings $settings */
    $settings = $this->settings;

    if (!$this->validateConfig($settings)) {
      return FALSE;
    }

    // Creates a and sets data to TwitterOAuth object.
    return new $class_name($settings->getConsumerKey(), $settings->getConsumerSecret());
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
   * @param \Drupal\social_auth_twitter\Settings\TwitterAuthSettings $settings
   *   The Twitter auth settings.
   *
   * @return bool
   *   True if module is configured, False otherwise.
   */
  protected function validateConfig(TwitterAuthSettings $settings) {
    $consumer_key = $settings->getConsumerKey();
    $consumer_secret = $settings->getConsumerSecret();

    if (!$consumer_key || !$consumer_secret) {
      $this->loggerFactory
        ->get('social_auth_twitter')
        ->error('Define Consumer Key and Consumer Secret on module settings.');

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
    $this->sessionHandler->setPrefix('social_auth_twitter_');

    return $this->sessionHandler;
  }

}

<?php

namespace Drupal\social_auth_twitter\Plugin\Network;

use Drupal\social_auth\Plugin\Network\SocialAuthNetwork;
use Drupal\social_api\SocialApiException;
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
   * {@inheritdoc}
   */
  public function initSdk() {
    $class_name = '\Abraham\TwitterOAuth\TwitterOAuth';

    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The PHP SDK for Twitter Client could not be found. Class: %s.', $class_name));
    }

    /* @var \Drupal\social_auth_twitter\Settings\TwitterAuthSettings $settings */
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
   * @return bool True if module is configured
   *   True if module is configured
   *   False otherwise
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
    $data_handler = \Drupal::service('social_sso.session_persistent_data_handler');
    $data_handler->setPrefix('social_auth_twitter_');

    return $data_handler;
  }

}

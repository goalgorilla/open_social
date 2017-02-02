<?php

namespace Drupal\social_sso;

use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Class AuthManager
 * @package Drupal\social_sso
 */
abstract class AuthManager implements AuthManagerInterface {

  protected $urlGenerator;
  protected $entityFieldManager;
  protected $loggerFactory;

  /**
   * Object of SDK to work with API of social network.
   *
   * @var object
   */
  protected $sdk;

  protected $profile;

  /**
   * AuthManager constructor.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $urlGenerator
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   */
  public function __construct(UrlGeneratorInterface $urlGenerator, EntityFieldManagerInterface $entity_field_manager, LoggerChannelFactoryInterface $logger_factory) {
    $this->urlGenerator = $urlGenerator;
    $this->entityFieldManager = $entity_field_manager;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl($type) {
    $key = $this->getSocialNetworkKey();

    return $this->urlGenerator->generateFromRoute("social_auth_{$key}.user_{$type}_callback", [], [
      'absolute' => TRUE,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getPreferredResolution() {
    $field_definitions = $this->entityFieldManager->getFieldDefinitions('profile', 'profile');

    // Check whether field is exists.
    if (!isset($field_definitions['field_profile_image'])) {
      return FALSE;
    }

    $max_resolution = $field_definitions['field_profile_image']->getSetting('max_resolution');
    $min_resolution = $field_definitions['field_profile_image']->getSetting('min_resolution');

    // Return order: max resolution, min resolution, FALSE.
    if ($max_resolution) {
      $resolution = $max_resolution;
    }
    elseif ($min_resolution) {
      $resolution = $min_resolution;
    }
    else {
      return FALSE;
    }

    $dimensions = explode('x', $resolution);

    return array_combine(['width', 'height'], $dimensions);
  }

  /**
   * {@inheritdoc}
   */
  public function getUsername() {
    return FALSE;
  }

}
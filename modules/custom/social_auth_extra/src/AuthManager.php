<?php

namespace Drupal\social_auth_extra;

use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Class AuthManager
 * @package Drupal\social_auth_extra
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

  /**
   * Contains object of a profile received from a social network.
   *
   * @var mixed
   */
  protected $profile;

  /**
   * Contains the field definition with a profile picture.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $fieldPicture;

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
    // Check whether field is exists.
    if (!$this->fieldPicture instanceof FieldDefinitionInterface) {
      return FALSE;
    }

    $max_resolution = $this->fieldPicture->getSetting('max_resolution');
    $min_resolution = $this->fieldPicture->getSetting('min_resolution');

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

  /**
   * {@inheritdoc}
   */
  public function setFieldPicture(FieldDefinitionInterface $field) {
    $this->fieldPicture = $field;
  }

}

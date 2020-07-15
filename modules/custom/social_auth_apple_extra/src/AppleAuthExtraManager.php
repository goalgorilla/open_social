<?php

namespace Drupal\social_auth_apple_extra;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\social_auth\AuthManager\OAuth2ManagerInterface;
use Drupal\social_auth_extra\AuthManager;
use League\OAuth2\Client\Provider\Apple;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Class AppleAuthExtraManager.
 *
 * @package Drupal\social_auth_apple_extra
 */
class AppleAuthExtraManager extends AuthManager {

  /**
   * The social auth Apple manager.
   *
   * @var \Drupal\social_auth\AuthManager\OAuth2ManagerInterface
   */
  protected $socialAuthAppleManager;

  /**
   * AppleAuthExtraManager constructor.
   *
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $urlGenerator
   *   The url generator.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\social_auth\AuthManager\OAuth2ManagerInterface $social_auth_apple_manager
   *   The social auth Apple manager.
   */
  public function __construct(
    UrlGeneratorInterface $urlGenerator,
    EntityFieldManagerInterface $entity_field_manager,
    LoggerChannelFactoryInterface $logger_factory,
    OAuth2ManagerInterface $social_auth_apple_manager
  ) {
    parent::__construct($urlGenerator, $entity_field_manager, $logger_factory);

    $this->socialAuthAppleManager = $social_auth_apple_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getSocialNetworkKey() {
    // TODO: Implement getSocialNetworkKey() method.
  }

  /**
   * {@inheritdoc}
   */
  public function setSdk($sdk) {
    if (!$sdk instanceof Apple) {
      throw new InvalidArgumentException('SDK object should be instance of \League\OAuth2\Client\Provider\Apple class');
    }

    $this->sdk = $sdk;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthenticationUrl($type, array $scope = []) {
    return $this->socialAuthAppleManager->setClient($this->sdk)
      ->getAuthorizationUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessToken($type) {
    // TODO: Implement getAccessToken() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getProfile() {
    // TODO: Implement getProfile() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getProfilePicture() {
    // TODO: Implement getProfilePicture() method.
  }

  /**
   * {@inheritdoc}
   */
  public function setAccessToken($access_token) {
    // TODO: Implement setAccessToken() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getAccountId() {
    // TODO: Implement getAccountId() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getFirstName() {
    // TODO: Implement getFirstName() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getLastName() {
    // TODO: Implement getLastName() method.
  }

}

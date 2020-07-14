<?php

namespace Drupal\social_auth_apple_extra;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\social_auth\AuthManager\OAuth2ManagerInterface;
use Drupal\social_auth_extra\AuthManager;
use League\OAuth2\Client\Provider\Apple;
use LinkedIn\Client;
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
   * @inheritDoc
   */
  public function getSocialNetworkKey() {
    // TODO: Implement getSocialNetworkKey() method.
  }

  /**
   * @inheritDoc
   */
  public function setSdk($sdk) {
    if (!$sdk instanceof Apple) {
      throw new InvalidArgumentException('SDK object should be instance of \League\OAuth2\Client\Provider\Apple class');
    }

    $this->sdk = $sdk;
  }

  /**
   * @inheritDoc
   */
  public function getAuthenticationUrl($type, array $scope = []) {
    return $this->socialAuthAppleManager->setClient($this->sdk)
      ->getAuthorizationUrl();
  }

  /**
   * @inheritDoc
   */
  public function getAccessToken($type) {
    // TODO: Implement getAccessToken() method.
  }

  /**
   * @inheritDoc
   */
  public function getProfile() {
    // TODO: Implement getProfile() method.
  }

  /**
   * @inheritDoc
   */
  public function getProfilePicture() {
    // TODO: Implement getProfilePicture() method.
  }

  /**
   * @inheritDoc
   */
  public function setAccessToken($access_token) {
    // TODO: Implement setAccessToken() method.
  }

  /**
   * @inheritDoc
   */
  public function getAccountId() {
    // TODO: Implement getAccountId() method.
  }

  /**
   * @inheritDoc
   */
  public function getFirstName() {
    // TODO: Implement getFirstName() method.
  }

  /**
   * @inheritDoc
   */
  public function getLastName() {
    // TODO: Implement getLastName() method.
  }

}

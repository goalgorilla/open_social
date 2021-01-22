<?php

namespace Drupal\social_auth_google;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\social_auth_extra\AuthManager;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Drupal\social_auth_google\Settings\GoogleAuthSettings;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class GoogleAuthManager.
 *
 * @package Drupal\social_auth_google
 */
class GoogleAuthManager extends AuthManager {

  /**
   * Holds the Google Service.
   *
   * @var \Google_Service_Oauth2
   */
  private $googleService;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * GoogleAuthManager constructor.
   *
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $urlGenerator
   *   The url generator.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(UrlGeneratorInterface $urlGenerator, EntityFieldManagerInterface $entity_field_manager, LoggerChannelFactoryInterface $logger_factory, RequestStack $request_stack) {
    parent::__construct($urlGenerator, $entity_field_manager, $logger_factory);

    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function getSocialNetworkKey() {
    return GoogleAuthSettings::getSocialNetworkKey();
  }

  /**
   * {@inheritdoc}
   */
  public function setSdk($sdk) {
    if (!$sdk instanceof \Google_Client) {
      throw new InvalidArgumentException('SDK object should be instance of \Google_Client class');
    }

    $this->sdk = $sdk;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthenticationUrl($type, array $scope = ['profile', 'email']) {
    $redirect_url = $this->getRedirectUrl($type);
    $this->sdk->setRedirectUri($redirect_url);

    return $this->sdk->createAuthUrl($scope);
  }

  /**
   * {@inheritdoc}
   */
  public function getProfilePicture() {
    return $this->profile->getPicture();
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessToken($type) {
    $redirect_url = $this->getRedirectUrl($type);
    $this->sdk->setRedirectUri($redirect_url);
    return $this->sdk->authenticate($this->requestStack->getCurrentRequest()->get('code'));
  }

  /**
   * {@inheritdoc}
   */
  public function getProfile() {
    if (empty($this->googleService)) {
      $this->googleService = new \Google_Service_Oauth2($this->sdk);
    }

    $this->profile = $this->googleService->userinfo->get();

    return $this->profile;
  }

  /**
   * {@inheritdoc}
   */
  public function setAccessToken($access_token) {
    $this->sdk->setAccessToken($access_token);
  }

  /**
   * {@inheritdoc}
   */
  public function getAccountId() {
    return $this->profile->getId();
  }

  /**
   * {@inheritdoc}
   */
  public function getFirstName() {
    return $this->profile->getGivenName();
  }

  /**
   * {@inheritdoc}
   */
  public function getLastName() {
    return $this->profile->getFamilyName();
  }

}

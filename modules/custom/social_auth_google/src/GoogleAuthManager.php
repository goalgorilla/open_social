<?php

namespace Drupal\social_auth_google;

use Drupal\social_auth_extra\AuthManager;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Drupal\social_auth_google\Settings\GoogleAuthSettings;

/**
 * Class GoogleAuthManager
 * @package Drupal\social_auth_google
 */
class GoogleAuthManager extends AuthManager {

  /** @var \Google_Service_Oauth2 $googleService */
  private $googleService;

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
    return $this->sdk->authenticate(\Drupal::request()->get('code'));
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

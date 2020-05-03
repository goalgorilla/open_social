<?php

namespace Drupal\social_auth_linkedin;

use Drupal\social_auth_extra\AuthManager;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use LinkedIn\Client;
use LinkedIn\Scope;
use Drupal\social_auth_linkedin\Settings\LinkedInAuthSettings;

/**
 * Class LinkedInAuthManager.
 *
 * @package Drupal\social_auth_linkedin
 */
class LinkedInAuthManager extends AuthManager {

  /**
   * {@inheritdoc}
   */
  public function getSocialNetworkKey() {
    return LinkedInAuthSettings::getSocialNetworkKey();
  }

  /**
   * {@inheritdoc}
   */
  public function setSdk($sdk) {
    if (!$sdk instanceof Client) {
      throw new InvalidArgumentException('SDK object should be instance of \LinkedIn\Client class');
    }

    $this->sdk = $sdk;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthenticationUrl($type, array $scope = [Scope::READ_LITE_PROFILE, Scope::READ_EMAIL_ADDRESS]) {
    return $this->sdk->getLoginUrl([
      'scope' => implode(',', $scope),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessToken($type = '') {
    try {
      $access_token = $this->sdk->getAccessToken();
      return $access_token;
    }
    catch (LinkedInException $e) {
      $this->loggerFactory
        ->get('social_auth_linkedin')
        ->error('Could not get LinkedIn access token. LinkedInException: @message', [
          '@message' => $e->getMessage(),
        ]);
      return NULL;
    }

    if ($access_token) {
      $this->sdk->setAccessToken($access_token);
      return $access_token;
    }

    $this->loggerFactory
      ->get('social_auth_linkedin')
      ->error('Could not get LinkedIn access token. User cancelled the dialog in LinkedIn or return URL was not valid.');

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getProfile() {
    $x =1;
    if (!$this->profile) {
      if (($profile = $this->sdk->get('v1/people/~:(id,firstName,lastName,email-address,formattedName,pictureUrls::(original))')) && !isset($profile['errorCode'])) {
        $this->profile = $profile;
      }
    }

    return $this->profile;
  }

  /**
   * {@inheritdoc}
   */
  public function getProfilePicture() {
    if (!empty($this->profile['pictureUrls']['_total'])) {
      return end($this->profile['pictureUrls']['values']);
    }
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
    return isset($this->profile['id']) ? $this->profile['id'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirstName() {
    return isset($this->profile['firstName']) ? $this->profile['firstName'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastName() {
    return isset($this->profile['lastName']) ? $this->profile['lastName'] : NULL;
  }

}

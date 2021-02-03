<?php

namespace Drupal\social_auth_linkedin;

use Drupal\social_auth_extra\AuthManager;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use LinkedIn\Client;
use LinkedIn\Scope;
use Drupal\social_auth_linkedin\Settings\LinkedInAuthSettings;
use LinkedIn\Exception;

/**
 * Class LinkedInAuthManager.
 *
 * @package Drupal\social_auth_linkedin
 */
class LinkedInAuthManager extends AuthManager {

  /**
   * Holds the LinkedIn SDK.
   *
   * @var \LinkedIn\Client
   */
  protected $sdk;

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
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthenticationUrl($type, array $scope = [Scope::READ_LITE_PROFILE, Scope::READ_EMAIL_ADDRESS]) {
    $redirect_url = $this->getRedirectUrl($type);

    // Set the redirect for the third party with our own.
    $this->sdk->setRedirectUrl($redirect_url);

    return $this->sdk->getLoginUrl([
      'scope' => implode(',', $scope),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessToken($type = '') {
    try {
      // Set the RedirectUrl before retrieving the access token.
      $redirect_url = $this->getRedirectUrl($type);
      $this->sdk->setRedirectUrl($redirect_url);

      $access_token = $this->sdk->getAccessToken($_GET['code']);
      return $access_token;
    }
    catch (Exception $e) {
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
    if (!$this->profile) {
      // Add basic profile information.
      if (($profile = $this->sdk->get('me', ['fields' => 'id,firstName,lastName'])) && !isset($profile['errorCode'])) {
        $this->profile['id'] = $profile['id'];
        $this->profile['basic_information'] = $profile;
      }
      if (($profile = $this->sdk->get('me', ['projection' => '(id,profilePicture(displayImage~:playableStreams))'])) && !isset($profile['errorCode'])) {
        $this->profile['profile_picture'] = $profile;
      }

      if (($profile = $this->sdk->get('emailAddress', ['q' => 'members', 'projection' => '(elements*(handle~))'])) && !isset($profile['errorCode'])) {
        $this->profile['email_information'] = $profile;
      }
    }

    return $this->profile;
  }

  /**
   * {@inheritdoc}
   */
  public function getProfilePicture() {
    if (!empty($this->profile['profile_picture']['profilePicture']['displayImage~']['elements'])) {
      $profile_picture = end($this->profile['profile_picture']['profilePicture']['displayImage~']['elements']);
      return $profile_picture['identifiers'][0]['identifier'];
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
    if (!empty($this->profile['basic_information']['firstName'])) {
      return array_values($this->profile['basic_information']['firstName']['localized'])[0];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLastName() {
    if (!empty($this->profile['basic_information']['lastName'])) {
      return array_values($this->profile['basic_information']['lastName']['localized'])[0];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEmailAddress() {
    if (!empty($this->profile['email_information']['elements'])) {
      return $this->profile['email_information']['elements'][0]['handle~']['emailAddress'];
    }
  }

}

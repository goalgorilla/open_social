<?php

namespace Drupal\social_auth_facebook;

use Drupal\social_auth_extra\AuthManager;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Drupal\social_auth_facebook\Settings\FacebookAuthSettings;

/**
 * Class FacebookAuthManager
 * @package Drupal\social_auth_facebook
 */
class FacebookAuthManager extends AuthManager {

  /**
   * {@inheritdoc}
   */
  public function getSocialNetworkKey() {
    return FacebookAuthSettings::getSocialNetworkKey();
  }

  /**
   * {@inheritdoc}
   */
  public function setSdk($sdk) {
    if (!$sdk instanceof Facebook) {
      throw new InvalidArgumentException('SDK object should be instance of \Facebook\Facebook class');
    }

    $this->sdk = $sdk;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthenticationUrl($type, array $scope = ['public_profile', 'email']) {
    $helper = $this->sdk->getRedirectLoginHelper();

    return $helper->getLoginUrl($this->getRedirectUrl($type), $scope);
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessToken($type) {
    $helper = $this->sdk->getRedirectLoginHelper();
    $redirect_url = $this->getRedirectUrl($type);

    try {
      $access_token = $helper->getAccessToken($redirect_url);
    }
    catch (FacebookResponseException $e) {
      $this->loggerFactory
        ->get('social_auth_facebook')
        ->error('Could not get Facebook access token. FacebookResponseException: @message', [
          '@message' => $e->getMessage(),
        ]);
      return NULL;
    }
    catch (FacebookSDKException $e) {
      $this->loggerFactory
        ->get('social_auth_facebook')
        ->error('Could not get Facebook access token. FacebookSDKException: @message', [
          '@message' => $e->getMessage(),
        ]);
      return NULL;
    }

    if ($access_token) {
      $this->sdk->setDefaultAccessToken($access_token);
      return $access_token;
    }

    $this->loggerFactory
      ->get('social_auth_facebook')
      ->error('Could not get Facebook access token. User cancelled the dialog in Facebook or return URL was not valid.');

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getProfile() {
    try {
      $this->profile = $this->sdk
        ->get('/me?fields=id,name,email,first_name,last_name')
        ->getGraphNode();
    }
    catch (FacebookResponseException $e) {
      $this->loggerFactory
        ->get('social_auth_facebook')
        ->error('Could not load Facebook user profile: FacebookResponseException: @message', [
          '@message' => $e->getMessage(),
        ]);
      return NULL;
    }
    catch (FacebookSDKException $e) {
      $this->loggerFactory
        ->get('social_auth_facebook')
        ->error('Could not load Facebook user profile: FacebookSDKException: @message', [
          '@message' => $e->getMessage(),
        ]);
      return NULL;
    }

    return $this->profile;
  }

  /**
   * {@inheritdoc}
   */
  public function getProfilePicture() {
    $resolution = $this->getPreferredResolution();
    $query = [
      'redirect' => 'false',
    ];

    if (is_array($resolution)) {
      $query += $resolution;
    }

    try {
      $graph_node = $this->sdk
        ->get('/me/picture?' . http_build_query($query))
        ->getGraphNode();

      $is_silhouette = (bool) $graph_node->getField('is_silhouette');

      if ($is_silhouette) {
        return FALSE;
      }

      return $graph_node->getField('url');
    }
    catch (FacebookResponseException $e) {
      $this->loggerFactory
        ->get('social_auth_facebook')
        ->error('Could not load Facebook profile picture: FacebookResponseException: @message', [
          '@message' => $e->getMessage(),
        ]);

      return NULL;
    }
    catch (FacebookSDKException $e) {
      $this->loggerFactory
        ->get('social_auth_facebook')
        ->error('Could not load Facebook profile picture: FacebookSDKException: @message', [
          '@message' => $e->getMessage(),
        ]);

      return NULL;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setAccessToken($access_token) {
    $this->sdk->setDefaultAccessToken($access_token);
  }

  /**
   * {@inheritdoc}
   */
  public function getAccountId() {
    if ($profile = $this->getProfile()) {
      return $profile->getField('id');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFirstName() {
    if ($profile = $this->getProfile()) {
      return $profile->getField('first_name');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLastName() {
    if ($profile = $this->getProfile()) {
      return $profile->getField('last_name');
    }
  }

}

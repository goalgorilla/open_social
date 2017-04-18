<?php

namespace Drupal\social_auth_twitter;

use Drupal\social_auth_extra\AuthManager;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Abraham\TwitterOAuth\TwitterOAuth;
use Abraham\TwitterOAuth\TwitterOAuthException;
use Drupal\social_auth_twitter\Settings\TwitterAuthSettings;

/**
 * Manages the authorization process before getting a long lived access token.
 */
class TwitterAuthManager extends AuthManager {

  /**
   * {@inheritdoc}
   */
  public function getSocialNetworkKey() {
    return TwitterAuthSettings::getSocialNetworkKey();
  }

  /**
   * {@inheritdoc}
   */
  public function setSdk($sdk) {
    if (!$sdk instanceof TwitterOAuth) {
      throw new InvalidArgumentException('SDK object should be instance of \Abraham\TwitterOAuth\TwitterOAuth class');
    }

    $this->sdk = $sdk;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthenticationUrl($type, array $scope = ['read']) {
    $request_token = $this->sdk->oauth('oauth/request_token', [
      'oauth_callback' => $this->getRedirectUrl($type),
      'x_auth_access_type' => current($scope),
    ]);

    $data_handler = \Drupal::service('plugin.network.manager')
      ->createInstance('social_auth_twitter')
      ->getDataHandler();

    $data_handler->set('oauth_token', $request_token['oauth_token']);
    $data_handler->set('oauth_token_secret', $request_token['oauth_token_secret']);

    return $this->sdk->url('oauth/authorize', [
      'oauth_token' => $request_token['oauth_token'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessToken($type) {
    // This method should not used for Twitter.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getProfile() {
    try {
      $this->profile = $this->sdk->get('account/verify_credentials', [
        'include_email' => 'true',
        'include_entities' => 'false',
        'skip_status' => 'true',
      ]);

      return $this->profile;
    } catch (TwitterOAuthException $e) {
      $this->loggerFactory
        ->get('social_auth_twitter')
        ->error('Could not load Twitter user profile: TwitterOAuthException: @message', [
          '@message' => $e->getMessage(),
        ]);

      return NULL;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getProfilePicture() {
    if (($profile = $this->getProfile()) && isset($profile->screen_name) && empty($profile->default_profile_image)) {
      return "https://twitter.com/{$profile->screen_name}/profile_image?size=original";
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setAccessToken($access_token) {
    $this->sdk->setOauthToken(
      $access_token['oauth_token'],
      $access_token['oauth_token_secret']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getAccountId() {
    if (($profile = $this->getProfile()) && isset($profile->id)) {
      return $profile->id;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFirstName() {
    // Twitter doesn't contain first name.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastName() {
    // Twitter doesn't contain last name.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsername() {
    if (($profile = $this->getProfile()) && isset($profile->screen_name)) {
      return $profile->screen_name;
    }

    return parent::getUsername();
  }

}

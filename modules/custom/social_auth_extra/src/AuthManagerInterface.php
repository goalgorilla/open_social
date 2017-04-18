<?php

namespace Drupal\social_auth_extra;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Interface AuthManagerInterface
 * @package Drupal\social_auth_extra
 */
interface AuthManagerInterface {

  /**
   * Returns key-name of a social network.
   *
   * @return string
   */
  public function getSocialNetworkKey();

  /**
   * Set instance of SDK.
   *
   * @param object $sdk
   *
   * @return mixed
   */
  public function setSdk($sdk);

  /**
   * Returns the login URL where user will be redirected for authentication.
   *
   * @param string $type
   *   Type of action. "login" or "register".
   * @param array $scope
   *   List of permissions which should be asked during authentication.
   *
   * @return string
   *   Absolute URL.
   */
  public function getAuthenticationUrl($type, array $scope = []);

  /**
   * Reads user's access token from social network.
   *
   * @param string $type
   *   Type of action. "login" or "register".
   *
   * @return object
   *   User's access token, if it could be read from social network.
   *   Null, otherwise.
   */
  public function getAccessToken($type);

  /**
   * Returns URL to authorize/registration depending on type.
   *
   * @param $type
   *   Type of action. "login" or "register".
   *
   * @return \Drupal\Core\GeneratedUrl|string
   */
  public function getRedirectUrl($type);

  /**
   * Returns object of a user profile.
   *
   * @return mixed
   */
  public function getProfile();

  /**
   * Returns URL of a profile picture.
   *
   * @return string|null
   *   Absolute URL to a picture or Null if picture is not set.
   */
  public function getProfilePicture();

  /**
   * Determines preferred profile pic resolution from account settings.
   *
   * Return order: max resolution, min resolution, FALSE.
   *
   * @return array|false
   *   Array of resolution, if defined in Drupal account settings.
   *   False otherwise.
   */
  public function getPreferredResolution();

  /**
   * Set access token to AuthManager to use it for API calls.
   *
   * @param mixed $access_token
   *
   * @return null
   */
  public function setAccessToken($access_token);

  /**
   * Returns an account ID on a social network.
   *
   * @return integer|string
   */
  public function getAccountId();

  /**
   * Returns first name on a social network if it possible.
   *
   * @return string|null
   */
  public function getFirstName();

  /**
   * Returns last name on a social network if it possible.
   *
   * @return string|null
   */
  public function getLastName();

  /**
   * Returns user on a social network if it possible.
   *
   * @return string|false
   */
  public function getUsername();

  /**
   * Set an instance of a field definition that contains picture.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   * @return null
   */
  public function setFieldPicture(FieldDefinitionInterface $field);

}

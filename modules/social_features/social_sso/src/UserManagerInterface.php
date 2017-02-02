<?php

namespace Drupal\social_sso;

use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\UserInterface;

/**
 * Interface UserManagerInterface
 * @package Drupal\social_sso
 */
interface UserManagerInterface {

  /**
   * Returns key-name of a social network.
   *
   * @return string
   */
  public function getSocialNetworkKey();

  /**
   * Creates object of a new account.
   *
   * @param array $values
   *   Additional fields to save in the created account.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Object of a new user account.
   */
  public function createAccount($values = []);

  /**
   * Creates object of a new profile.
   *
   * @param array $values
   *   Additional fields to save in the created profile.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Object of a new user profile.
   */
  public function createProfile($values = []);

  /**
   * Download and set the picture to profile
   *
   * @param $url
   *    Absolute URL of a picture.
   * @param $account_id
   *    Identifier of account on social network.
   *
   * @return bool
   */
  public function setProfilePicture($url, $account_id);

  /**
   * Saves the picture from URL.
   *
   * @param $url
   *   Absolute URL of a picture.
   * @param $account_id
   *   Identifier of account on social network.
   *
   * @return bool|object
   *   Object of created file or FALSE when error has occurred.
   */
  public function downloadProfilePicture($url, $account_id);

  /**
   * Returns directory path to save picture.
   *
   * @return bool|string
   *   Directory path or FALSE when error has occurred.
   */
  public function getPictureDirectory();

  /**
   * Set the account ID to the account on this site.
   *
   * @param string $account_id
   * @return null
   */
  public function setAccountId($account_id);

  /**
   * Get the account ID to the account on this site.
   *
   * @return string
   */
  public function getAccountId();

  /**
   * Set an instance of profile to user manager to use it later.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   * @return null
   */
  public function setProfile(ProfileInterface $profile);

  /**
   * Set an instance of user account to user manager to use it later.
   *
   * @param \Drupal\user\UserInterface $account
   * @return null
   */
  public function setAccount(UserInterface $account);

}
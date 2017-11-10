<?php

namespace Drupal\social_auth_extra;

use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Interface UserManagerInterface.
 *
 * @package Drupal\social_auth_extra
 */
interface UserManagerInterface {

  /**
   * Returns key-name of a social network.
   *
   * @return string
   *   Key-name of the social network.
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
  public function createAccount(array $values = []);

  /**
   * Creates object of a new profile.
   *
   * @param array $values
   *   Additional fields to save in the created profile.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Object of a new user profile.
   */
  public function createProfile(array $values = []);

  /**
   * Download and set the picture to profile.
   *
   * @param string $url
   *   Absolute URL of a picture.
   * @param string $account_id
   *   Identifier of account on social network.
   *
   * @return bool
   *   Returns TRUE if the picture was saved successfully, FALSE if it didn't.
   */
  public function setProfilePicture($url, $account_id);

  /**
   * Saves the picture from URL.
   *
   * @param string $url
   *   Absolute URL of a picture.
   * @param string $account_id
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
   *   Identifier of account on this site.
   *
   * @return null
   *   Returns null.
   */
  public function setAccountId($account_id);

  /**
   * Get the account ID to the account on this site.
   *
   * @return string
   *   Account ID on this site.
   */
  public function getAccountId();

  /**
   * Set an instance of profile to user manager to use it later.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   Profile instance.
   *
   * @return null
   *   Returns null.
   */
  public function setProfile(ProfileInterface $profile);

  /**
   * Set an instance of user account to user manager to use it later.
   *
   * @param \Drupal\user\UserInterface $account
   *   User account.
   *
   * @return null
   *   Returns null.
   */
  public function setAccount(UserInterface $account);

  /**
   * Set an instance of a field definition that contains picture.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   Field definition containing the picture.
   *
   * @return null
   *   Returns null.
   */
  public function setFieldPicture(FieldDefinitionInterface $field);

  /**
   * Set the profile type.
   *
   * @param string $profile_type
   *   Profile type.
   */
  public function setProfileType($profile_type);

}

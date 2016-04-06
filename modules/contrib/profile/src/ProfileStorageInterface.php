<?php

/**
 * @file
 * Contains \Drupal\profile\ProfileStorageInterface.
 */

namespace Drupal\profile;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an interface for profile entity storage.
 */
interface ProfileStorageInterface extends EntityStorageInterface {

  /**
   * Loads the given user's profile.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *    The user entity.
   * @param string $profile_type
   *    The profile type.
   * @param bool $active
   *    Boolean representing if profile active or not.
   *
   * @return \Drupal\profile\Entity\ProfileInterface
   *    The loaded profile entity.
   */
  public function loadByUser(AccountInterface $account, $profile_type, $active);

  /**
   * Loads the given user's profiles.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *    The user entity.
   * @param string $profile_type
   *    The profile type.
   * @param bool $active
   *    Boolean representing if profile active or not.
   *
   * @return \Drupal\profile\Entity\ProfileInterface[]
   *    An array of loaded profile entities.
   */
  public function loadMultipleByUser(AccountInterface $account, $profile_type, $active);

  /**
   * Loads the default user profile.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *    The user entity.
   * @param string $profile_type
   *    The profile type.
   *
   * @return \Drupal\profile\Entity\ProfileInterface
   *    An array of loaded profile entities.
   */
  public function loadDefaultByUser(AccountInterface $account, $profile_type);

}

<?php

/**
 * @file
 * Contains \Drupal\group\Access\GroupPermissionsHashGeneratorInterface.
 */

namespace Drupal\group\Access;

use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the group permissions hash generator interface.
 */
interface GroupPermissionsHashGeneratorInterface {

  /**
   * Generates a hash that uniquely identifies a group member's permissions.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group for which to get the permissions hash.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to get the permissions hash.
   *
   * @return string
   *   A permissions hash.
   */
  public function generate(GroupInterface $group, AccountInterface $account);

}

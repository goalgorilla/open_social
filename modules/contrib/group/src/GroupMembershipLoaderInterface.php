<?php

/**
 * @file
 * Contains \Drupal\group\GroupMembershipLoaderInterface.
 */

namespace Drupal\group;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Defines the group membership loader interface.
 */
interface GroupMembershipLoaderInterface {

  /**
   * Loads a membership by group and user.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to load the membership from.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user to load the membership for.
   *
   * @return \Drupal\group\GroupMembership|false
   *   The loaded GroupMembership or FALSE if none was found.
   */
  public function load(GroupInterface $group, AccountInterface $account);

  /**
   * Loads all memberships for a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to load the memberships from.
   * @param string|array $roles
   *   (optional) A group role machine name or a list of group role machine
   *   names to filter on. Valid results only need to match on one role.
   *
   * @return \Drupal\group\GroupMembership[]
   *   The loaded GroupMemberships matching the criteria.
   */
  public function loadByGroup(GroupInterface $group, $roles = NULL);

  /**
   * Loads all memberships for a user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user to load the membership for. Leave blank to load the
   *   memberships of the currently logged in user.
   * @param string|array $roles
   *   (optional) A group role machine name or a list of group role machine
   *   names to filter on. Valid results only need to match on one role.
   *
   * @return \Drupal\group\GroupMembership[]
   *   The loaded GroupMemberships matching the criteria.
   */
  public function loadByUser(AccountInterface $account = NULL, $roles = NULL);

}

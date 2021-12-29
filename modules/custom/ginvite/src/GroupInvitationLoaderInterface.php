<?php

namespace Drupal\ginvite;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\ginvite\Plugin\GroupContentEnabler\GroupInvitation;

/**
 * Defines the group invitation loader interface.
 */
interface GroupInvitationLoaderInterface {

  /**
   * Loads a invitation by group and user.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to load the invitation from.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user to load the invitation for.
   *
   * @return \Drupal\ginvite\GroupInvitation|false
   *   The loaded GroupInvitation or FALSE if none was found.
   */
  public function load(GroupInterface $group, AccountInterface $account);

  /**
   * Loads all invitations for a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to load the invitations from.
   * @param string|array $roles
   *   (optional) A group role machine name or a list of group role machine
   *   names to filter on. Valid results only need to match on one role.
   *
   * @return \Drupal\ginvite\GroupInvitation[]
   *   The loaded GroupInvitations matching the criteria.
   */
  public function loadByGroup(GroupInterface $group, $roles = NULL);

  /**
   * Loads all invitations for a user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user to load the invitation for. Leave blank to load the
   *   invitations of the currently logged in user.
   * @param string|array $roles
   *   (optional) A group role machine name or a list of group role machine
   *   names to filter on. Valid results only need to match on one role.
   * @param int $status
   *   Invitation status.
   *
   * @return \Drupal\ginvite\GroupInvitation[]
   *   The loaded GroupInvitations matching the criteria.
   */
  public function loadByUser(AccountInterface $account = NULL, $roles = NULL, $status = GroupInvitation::INVITATION_PENDING);

  /**
   * Load Invitations by their property values.
   *
   * @param array $values
   *   An associative array where the keys are the property names and the
   *   values are the values those properties must have.
   *
   * @return \Drupal\ginvite\GroupInvitation[]
   *   The loaded GroupInvitations matching the criteria.
   */
  public function loadByProperties(array $values);

}

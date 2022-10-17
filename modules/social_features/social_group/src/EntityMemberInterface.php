<?php

namespace Drupal\social_group;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a common interface for entities that support memberships.
 */
interface EntityMemberInterface extends ContentEntityInterface {

  /**
   * Checks if a user is a member.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account object.
   */
  public function hasMember(AccountInterface $account): bool;

}

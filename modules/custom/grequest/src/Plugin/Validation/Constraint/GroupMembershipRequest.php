<?php

namespace Drupal\grequest\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a user a member of the group.
 *
 * @Constraint(
 *   id = "GroupMembershipRequest",
 *   label = @Translation("Group request membership checks", context = "Validation"),
 *   type = "entity:group_content"
 * )
 */
class GroupMembershipRequest extends Constraint {

  /**
   * The message to show when a user is already a member of the group.
   *
   * @var string
   */
  public $message = 'User "%name" is already a member of group';

}

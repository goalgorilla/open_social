<?php

namespace Drupal\grequest\Plugin\Group\RelationHandler;

use Drupal\group\Plugin\Group\RelationHandler\AccessControlInterface;
use Drupal\group\Plugin\Group\RelationHandler\AccessControlTrait;

/**
 * Checks access for the grequest relation plugin.
 */
class GroupMembershipRequestAccessControl implements AccessControlInterface {

  use AccessControlTrait;

  /**
   * Constructs a new GroupInvitationAccessControl.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\AccessControlInterface $parent
   *   The parent access control handler.
   */
  public function __construct(AccessControlInterface $parent) {
    $this->parent = $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsOperation($operation, $target) {
    return $this->parent->supportsOperation($operation, $target);
  }

}

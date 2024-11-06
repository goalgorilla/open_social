<?php

namespace Drupal\social_group_invite\Plugin\Group\RelationHandler;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\Plugin\Group\RelationHandler\AccessControlInterface;
use Drupal\group\Plugin\Group\RelationHandler\AccessControlTrait;

/**
 * Checks access for the group_invitation relation plugin.
 */
class SocialGroupInviteAccessControl implements AccessControlInterface {

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
  public function relationshipAccess(GroupRelationshipInterface $group_relationship, $operation, AccountInterface $account, $return_as_object = FALSE): bool|AccessResultInterface {
    if (!isset($this->parent)) {
      throw new \LogicException('Using AccessControlTrait without assigning a parent or overwriting the methods.');
    }

    // User who receives the invite should be able to view it.
    if ($group_relationship->getEntityId() === $account->id()) {
      return AccessResult::allowed();
    }

    return $this->parent->relationshipAccess($group_relationship, $operation, $account, $return_as_object);
  }

}

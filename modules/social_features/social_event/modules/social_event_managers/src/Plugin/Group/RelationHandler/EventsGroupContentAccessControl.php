<?php

namespace Drupal\social_event_managers\Plugin\Group\RelationHandler;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Plugin\Group\RelationHandler\AccessControlInterface;
use Drupal\group\Plugin\Group\RelationHandler\AccessControlTrait;
use Drupal\node\NodeInterface;
use Drupal\social_event_managers\SocialEventManagersAccessHelper;

/**
 * Provides access control for Event GroupContent entities.
 *
 * @todo Check if the access handler not need anymore if favor of "social_event_managers_node_access()".
 */
class EventsGroupContentAccessControl implements AccessControlInterface {

  use AccessControlTrait;

  /**
   * Constructs a new GroupMembershipAccessControl.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandlerDefault\AccessControl $parent
   *   The parent access control handler.
   */
  public function __construct(AccessControlInterface $parent) {
    $this->parent = $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account, $return_as_object = FALSE) {
    if (!isset($this->parent)) {
      throw new \LogicException('Using AccessControlTrait without assigning a parent or overwriting the methods.');
    }

    // We only care about Update (/edit) of the Event content.
    if ($operation !== 'update') {
      return $this->parent->entityAccess($entity, $operation, $account, $return_as_object);
    }

    if (!$entity instanceof NodeInterface || $entity->bundle() !== 'event') {
      return $this->parent->entityAccess($entity, $operation, $account, $return_as_object);
    }

    /** @var \Drupal\node\NodeInterface $entity */
    if ($entity->get('field_event_managers')->isEmpty()) {
      return $this->parent->entityAccess($entity, $operation, $account, $return_as_object);
    }

    $result = SocialEventManagersAccessHelper::getEntityAccessResult($entity, $operation, $account);

    // If we did not allow access, we need to explicitly forbid access to avoid
    // other modules from granting access where Group promised the entity would
    // be inaccessible.
    if (!$result->isAllowed()) {
      $result = AccessResult::forbidden()->addCacheContexts(['user.group_permissions']);
    }

    $result->cachePerUser();

    return $return_as_object ? $result : $result->isAllowed();
  }

}

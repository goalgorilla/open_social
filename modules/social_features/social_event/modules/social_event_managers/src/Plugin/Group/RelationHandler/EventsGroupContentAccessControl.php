<?php

namespace Drupal\social_event_managers\Plugin\Group\RelationHandler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Plugin\Group\RelationHandler\AccessControlInterface;
use Drupal\group\Plugin\Group\RelationHandler\AccessControlTrait;
use Drupal\node\NodeInterface;
use Drupal\social_event_managers\SocialEventManagersAccessHelper;

/**
 * Provides access control for Event GroupContent entities.
 */
class EventsGroupContentAccessControl implements AccessControlInterface {

  use AccessControlTrait;

  /**
   * Constructs a new AccessControl for event manager permissions.
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

    // We only care about the update of the Event content
    // and if the field_event_managers is not empty.
    if ($operation !== 'update'
      || !$entity instanceof NodeInterface
      || $entity->bundle() !== 'event'
      || !SocialEventManagersAccessHelper::isEventNodeWithManagers($entity)
      ) {
      return $this->parent->entityAccess($entity, $operation, $account, $return_as_object);
    }

    // We need to make sure that event managers have the access.
    $result = SocialEventManagersAccessHelper::getEntityAccessResult($entity, $operation, $account);

    return $return_as_object ? $result : $result->isAllowed();
  }

}

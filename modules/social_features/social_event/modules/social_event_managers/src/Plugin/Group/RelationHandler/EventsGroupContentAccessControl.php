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
 */
class EventsGroupContentAccessControl implements AccessControlInterface {

  use AccessControlTrait;

  /**
   * Constructs a new AccessControl for event manager permissions.
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
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account, $return_as_object = FALSE) {
    if (!isset($this->parent)) {
      throw new \LogicException('Using AccessControlTrait without assigning a parent or overwriting the methods.');
    }

    $parent_result = $this->parent->entityAccess($entity, $operation, $account, TRUE);
    assert($parent_result instanceof AccessResult);

    // We only care about the update of the Event content
    // and if the field_event_managers is not empty.
    if (!$this->isValidEventManagerUpdate($operation, $entity)) {
      return $return_as_object ? $parent_result : $parent_result->isAllowed();
    }

    // Prefer event-manager allow; otherwise use parent group access.
    // Avoid orIf(): Group "forbidden" would override manager "allowed".
    if ($entity instanceof NodeInterface) {
      $event_manager_result = SocialEventManagersAccessHelper::getEntityAccessResult($entity, $operation, $account);
      if ($event_manager_result->isAllowed()) {
        $result = $event_manager_result->addCacheableDependency($parent_result);
      }
      else {
        $result = $parent_result->addCacheableDependency($event_manager_result);
      }
      return $return_as_object ? $result : $result->isAllowed();
    }

    return $return_as_object ? $parent_result : $parent_result->isAllowed();
  }

  /**
   * Checks if the update on event is valid.
   *
   * @param string $operation
   *   Operation.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The node entity.
   */
  public function isValidEventManagerUpdate(string $operation, EntityInterface $entity): bool {
    return $operation == 'update'
      && $entity instanceof NodeInterface
      && $entity->bundle() == 'event'
      && SocialEventManagersAccessHelper::isEventNodeWithManagers($entity);
  }

}

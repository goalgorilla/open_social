<?php

namespace Drupal\social_event_managers\Hooks;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\hux\Attribute\Hook;
use Drupal\node\NodeInterface;
use Drupal\social_event_managers\SocialEventManagersAccessHelper;

/**
 * Contains entity related hooks.
 */
final readonly class NodeAccessHooks {

  /**
   * Implements hook_ENTITY_TYPE_access().
   *
   * Remember: if any module returns forbidden and denies access to certain node
   * and operation, it will not allow the user to do the operation on the node.
   *
   * Initially, this hook was written for providing access in groups.
   * But the group has moved to RelationShipHandler access control plugins.
   * We will still keep this in place for the events which may be inside any
   * group.
   */
  #[Hook('node_access')]
  public function nodeAccess(EntityInterface $entity, string $operation, AccountInterface $account): AccessResultInterface {
    if ($entity instanceof NodeInterface &&
      SocialEventManagersAccessHelper::isEventNodeWithManagers($entity)) {
      return SocialEventManagersAccessHelper::getEntityAccessResult($entity, $operation, $account);
    }
    return AccessResult::neutral();
  }

}

<?php

namespace Drupal\entity_access_by_field\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_access_by_field\EntityAccessHelper;
use Drupal\node\NodeAccessControlHandler;
use Drupal\node\NodeInterface;

/**
 * Provides access control for node entities within a group.
 *
 * @package Drupal\entity_access_by_field\Access
 */
class NodeVisibilityFieldACH extends NodeAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // Lets see if we actually have access for our node.
    if ($entity instanceof NodeInterface) {
      // We grab the current nodeAccessCheck for a given user, node and
      // operation.
      $result = EntityAccessHelper::getEntityAccessResult($entity, $operation, $account);
      // And we return it similar to parent::access.
      if ($result instanceof AccessResult) {
        // Since we are covering group membership related nodes, lets cache
        // per user. As it's more than just global permissions.
        $result->cachePerUser();
        return $return_as_object ? $result : $result->isAllowed();
      }
    }

    return parent::access($entity, $operation, $account, $return_as_object);
  }

}

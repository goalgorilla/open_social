<?php

namespace Drupal\social_event_managers;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\node\NodeInterface;

/**
 * Helper class for checking update access on event managers nodes.
 */
class SocialEventManagersAccessHelper {

  /**
   * NodeAccessCheck for given operation, node and user account.
   */
  public static function nodeAccessCheck(NodeInterface $node, $op, AccountInterface $account) {
    if ($op === 'update') {

      // Only for events.
      if ($node->getType() === 'event') {
        // Only continue if the user has access to view the event.
        if ($node->access('view', $account)) {
          $event_managers = $node->get('field_event_managers')->getValue();

          foreach ($event_managers as $event_manager) {
            if ($account->id() == $event_manager['target_id']) {
              return 2;
            }
          }

          // No hits, so we assume the user is not an event manager.
          return 1;
        }
      }
    }
    return 0;
  }

  /**
   * Gets the Entity access for the given node.
   */
  public static function getEntityAccessResult(NodeInterface $node, $op, AccountInterface $account) {
    $access = SocialEventManagersAccessHelper::nodeAccessCheck($node, $op, $account);

    switch ($access) {
      case 2:
        return AccessResult::allowed()->cachePerPermissions()->addCacheableDependency($node);

      case 1:
        return AccessResult::forbidden();
    }

    return AccessResult::neutral();
  }

}

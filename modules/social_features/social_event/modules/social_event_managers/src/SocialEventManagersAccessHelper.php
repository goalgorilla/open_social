<?php

namespace Drupal\social_event_managers;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\node\NodeInterface;

/**
 * Helper class for checking update access on event managers nodes.
 *
 * @todo Check: probably, these access rules should be applied only for events
 *   with not empty "field_event_managers" field.
 */
class SocialEventManagersAccessHelper {

  /**
   * Neutral status.
   */
  const NEUTRAL = 0;

  /**
   * Forbidden status.
   */
  const FORBIDDEN = 1;

  /**
   * Allowed status.
   */
  const ALLOW = 2;

  /**
   * NodeAccessCheck for given operation, node and user account.
   */
  public static function nodeAccessCheck(NodeInterface $node, $op, AccountInterface $account): int {
    if ($op !== 'update') {
      return static::NEUTRAL;
    }

    // Only for events.
    if ($node->bundle() !== 'event') {
      return static::NEUTRAL;
    }

    if ($account->hasPermission('administer nodes')
      || $account->hasPermission('bypass node access')) {
      return static::ALLOW;
    }

    // Only continue if the user has access to view the event.
    if ($node->access('view', $account)) {
      // The owner has access.
      if ($account->id() === $node->getOwnerId()) {
        return static::ALLOW;
      }

      $event_managers = $node->get('field_event_managers')->getValue();

      foreach ($event_managers as $event_manager) {
        if (isset($event_manager['target_id']) && $account->id() === $event_manager['target_id']) {
          return static::ALLOW;
        }
      }

      // No hits, so we assume the user is not an event manager and returns
      // "neutral" access result to make possible to apply others
      // access rules.
      return static::NEUTRAL;
    }

    return static::NEUTRAL;
  }

  /**
   * Gets the Entity access for the given node.
   */
  public static function getEntityAccessResult(NodeInterface $node, $op, AccountInterface $account): AccessResult {
    $access = self::nodeAccessCheck($node, $op, $account);

    switch ($access) {
      case static::ALLOW:
        return AccessResult::allowed()->cachePerPermissions()->addCacheableDependency($node);

      case static::FORBIDDEN:
        return AccessResult::forbidden();
    }

    return AccessResult::neutral();
  }

}

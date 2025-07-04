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
   * Neutral status.
   */
  protected const NEUTRAL = 0;

  /**
   * Forbidden status.
   */
  protected const FORBIDDEN = 1;

  /**
   * Allowed status.
   */
  protected const ALLOW = 2;

  /**
   * NodeAccessCheck for given operation, node and user account.
   */
  public static function nodeAccessCheck(NodeInterface $node, $op, AccountInterface $account): int {
    // We only care about Update (/edit) of the Event content.
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
        return AccessResult::allowed()
          // We need to reassess access check if permissions are changed
          // or if user entity is changed.
          ->cachePerPermissions()
          ->cachePerUser()
          ->addCacheableDependency($node);

      case static::FORBIDDEN:
        return AccessResult::forbidden();
    }

    return AccessResult::neutral();
  }

  /**
   * Checks if a node is an event and is having event managers.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The node entity.
   *
   * @return bool
   *   TRUE or FALSE depending upon validation of conditions.
   */
  public static function isEventNodeWithManagers(NodeInterface $entity): bool {
    return $entity->getType() === 'event'
      && !$entity->get('field_event_managers')->isEmpty();
  }

}

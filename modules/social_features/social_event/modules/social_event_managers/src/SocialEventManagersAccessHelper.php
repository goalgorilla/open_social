<?php

namespace Drupal\social_event_managers;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\node\NodeInterface;
use Drupal\social_event\Entity\EventEnrollment;

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
        if ($account->hasPermission('administer nodes')
          || $account->hasPermission('bypass node access')) {
          return 2;
        }
        // Only continue if the user has access to view the event.
        if ($node->access('view', $account)) {
          // The owner has access.
          if ($account->id() === $node->getOwnerId()) {
            return 2;
          }

          $event_managers = $node->get('field_event_managers')->getValue();

          foreach ($event_managers as $event_manager) {
            if (isset($event_manager['target_id']) && $account->id() === $event_manager['target_id']) {
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
    $access = self::nodeAccessCheck($node, $op, $account);

    switch ($access) {
      case 2:
        return AccessResult::allowed()->cachePerPermissions()->addCacheableDependency($node);

      case 1:
        return AccessResult::forbidden();
    }

    return AccessResult::neutral();
  }

  /**
   * Event enrollment acces for given operation.
   */
  public function eventEnrollmentAccessCheck(EventEnrollment $event_enrollment, string $op, AccountInterface $account): AccessResult {
    // This allows view access to event_enrollment entities for users which are
    // the recipients of the event enrollment but not the owner of the entity.
    // For example a site manager can create an enrollment for a specific user.
    if ($op !== 'view') {
      // If we are doing different operations than viewing, then let other
      // access checks to determine the access.
      return AccessResult::neutral();
    }

    $enrollment_status = $event_enrollment->get('field_enrollment_status')->getString();
    if (!(bool) $enrollment_status) {
      // Return neutral and let other access checks acts on this.
      return AccessResult::neutral();
    }

    $owner = $event_enrollment->getOwner();
    if (!$owner->hasPermission('view published event enrollment entities')) {
      // Return neutral and let other access checks acts on this.
      return AccessResult::neutral();
    }

    $field_account_id = $event_enrollment->get('field_account')->getString();
    if ((int) $field_account_id !== (int) $account->id()) {
      // If current user is not the recipient then let other
      // part of the system determine the access, by default it should
      // be access denied, but it could be overwritten, so we don't make
      // any assumptions here.
      return AccessResult::neutral();
    }

    // The user is a recipient, so we allow access to event enrollment.
    return AccessResult::allowed();
  }

}

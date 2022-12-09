<?php

namespace Drupal\social_event\Access;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\social_event\Entity\Node\Event;
use Symfony\Component\Routing\Route;

/**
 * Implements access checker for enrollments in the event.
 */
class EventEnrollmentsAccessCheck implements AccessInterface {

  /**
   * Check access for enrollments pages in the event.
   */
  public function access(Route $route, AccountInterface $account, RouteMatchInterface $route_match): AccessResultInterface {
    // Skip for administrators.
    if (
      $account->hasPermission('bypass node access') ||
      $account->hasPermission('administer event enrollment entities')
    ) {
      return AccessResult::allowed();
    }

    $node = $route_match->getParameter('node');
    if (!$node instanceof NodeInterface) {
      // On views url parameters node can be provided as raw parameter.
      $nid = $route_match->getRawParameter('node');
      if (!empty($nid)) {
        $node = Node::load($nid);
      }
    }

    // Apply only for event node type.
    if (!$node instanceof Event) {
      // Returning `AccessResult::forbidden()` explicitly is better because for
      // routes access checks to returning an `AccessResult::neutral()` and
      // an `AccessResult::forbidden()` object does not make any difference.
      // @see https://www.drupal.org/docs/8/api/routing-system/access-checking-on-routes/route-access-checking-basics
      return AccessResult::forbidden();
    }

    // Allow access for event author.
    if ($node->getOwnerId() === $account->id()) {
      return AccessResult::allowed();
    }

    return AccessResult::allowedIf($node->showEnrollments());
  }

}

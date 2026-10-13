<?php

declare(strict_types=1);

namespace Drupal\social_event\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Drupal\social_event\Entity\Node\Event;
use Drupal\social_event\EventEnrollmentInterface;

/**
 * Denies native enrolment routes when an event uses external registration.
 */
class ExternalRegistrationEnrollmentAccess implements AccessInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Checks that no event on the route uses external registration.
   *
   * Both the route node and the event of the route enrolment are checked, so
   * pairing a native event with an enrolment of an external event is denied.
   */
  public function access(RouteMatchInterface $route_match): AccessResultInterface {
    $node = $this->routeEvent($route_match);
    $enrollment_event = $this->enrollmentEvent($route_match);

    $result = AccessResult::allowed();
    foreach ([$node, $enrollment_event] as $event) {
      if ($event instanceof Event) {
        $result->addCacheableDependency($event);
      }
    }

    if ($node instanceof Event && $enrollment_event instanceof Event && $node->id() !== $enrollment_event->id()) {
      return AccessResult::forbidden('The enrolment does not belong to this event.')->addCacheableDependency($result);
    }

    if ($node?->isExternalRegistration() || $enrollment_event?->isExternalRegistration()) {
      return AccessResult::forbidden('The event uses external registration.')->addCacheableDependency($result);
    }

    return $result;
  }

  /**
   * Loads the event from the route node parameter.
   */
  private function routeEvent(RouteMatchInterface $route_match): ?Event {
    $node = $route_match->getParameter('node');
    if (!$node instanceof NodeInterface) {
      $nid = $route_match->getRawParameter('node');
      if (is_scalar($nid) && $nid !== '') {
        $node = $this->entityTypeManager->getStorage('node')->load((int) $nid);
      }
    }

    return $node instanceof Event ? $node : NULL;
  }

  /**
   * Loads the event of the route enrolment parameter.
   */
  private function enrollmentEvent(RouteMatchInterface $route_match): ?Event {
    $enrollment = $route_match->getParameter('event_enrollment');
    if (!$enrollment instanceof EventEnrollmentInterface) {
      return NULL;
    }

    $event = $enrollment->get('field_event')->entity;
    return $event instanceof Event ? $event : NULL;
  }

}

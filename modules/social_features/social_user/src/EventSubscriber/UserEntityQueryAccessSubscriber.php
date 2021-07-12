<?php

declare(strict_types=1);

namespace Drupal\social_user\EventSubscriber;

use Drupal\entity\QueryAccess\QueryAccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Access handler for User Entity Queries.
 *
 * Uses the event from the entity module with the `EventOnlyQueryAccessHandler`
 * class.
 */
class UserEntityQueryAccessSubscriber implements EventSubscriberInterface {

  /**
   * Called when access is calculated for a user entity query.
   *
   * Restricts user listing queries to users that are allowed to view other
   * users.
   *
   * @param \Drupal\entity\QueryAccess\QueryAccessEvent $event
   *   The query access event.
   */
  public function onUserQuery(QueryAccessEvent $event) : void {
    // If a user is not allowed to view profiles we deny them access.
    if (!$event->getAccount()->hasPermission('access user profiles')) {
      $event->getConditions()->alwaysFalse();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() : array {
    return [
      'entity.query_access.user' => ["onUserQuery"],
    ];
  }

}

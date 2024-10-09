<?php

declare(strict_types=1);

namespace Drupal\social_event_managers\EventSubscriber;

use Drupal\social_node\Event\NodeQueryAccessEvent;
use Drupal\social_node\Event\SocialNodeEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NodeQueryAccessAlterSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[SocialNodeEvents::NODE_ACCESS_QUERY_ALTER][] = ['alterQueryConditions'];
    return $events;
  }

  /**
   * Alter query conditions.
   *
   * @param \Drupal\social_node\Event\NodeQueryAccessEvent $event
   *   The event object.
   *
   * @throws \Exception
   */
  public function alterQueryConditions(NodeQueryAccessEvent $event): void {
    $account = $event->account();
    if ($account->isAnonymous()) {
      return;
    }

    $or = $event->getConditions();

    // Make sure we have joined a table with event managers.
    $managers_table = $event->ensureJoinNodeField('field_event_managers');
    $or->condition("$managers_table.field_event_managers_target_id", $account->id());
  }

}


<?php

declare(strict_types=1);

namespace Drupal\social_event_managers\EventSubscriber;

use Drupal\social_node\Event\NodeQueryAccessEvent;
use Drupal\social_node\Event\SocialNodeEvents;
use Drupal\social_node\SocialNodeQueryAccessAlterInterface;

/**
 * Alter query access for nodes with event managers.
 */
class NodeQueryAccessAlterSubscriber implements SocialNodeQueryAccessAlterInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[SocialNodeEvents::NODE_QUERY_ACCESS_ALTER][] = ['alterQueryAccess'];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function alterQueryAccess(NodeQueryAccessEvent $event): void {
    $account = $event->account();
    if ($account->isAnonymous()) {
      return;
    }

    $or = $event->getConditions();

    // Make sure we have joined a table with event managers.
    $managers_table = $event->ensureNodeFieldTableJoin('field_event_managers');
    $or->condition("$managers_table.field_event_managers_target_id", $account->id());
  }

}

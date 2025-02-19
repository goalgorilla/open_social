<?php

declare(strict_types=1);

namespace Drupal\social_event_managers\EventSubscriber;

use Drupal\social_node\Event\NodeQueryAccessEvent;
use Drupal\social_node\Event\SocialNodeEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Alter query access for nodes with event managers.
 */
class NodeQueryAccessAlterSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    if (!class_exists(SocialNodeEvents::class)) {
      return [];
    }

    $events[SocialNodeEvents::NODE_QUERY_ACCESS_ALTER][] = ['alterQueryAccess'];
    return $events;
  }

  /**
   * Alter query access for node entities.
   *
   *   This method allows adding query access rule directly to the node
   *   query object.
   *
   *   The $event contains "OR" condition group with all provided node query
   *   access rules, so you should add your own query access rule to this
   *   group.
   *
   * For example, you want to restrict node access to a specific role and node
   * field value:
   * @code
   *   $or = $event->getConditions();
   *   $field_table = $event->ensureNodeFieldTableJoin('field_genre');
   *   if ($event->account()->hasRole('writer')) {
   *     $or->condition("$field_table.field_genre_value", 'adventure');
   *   }
   * @endcode
   *
   * Or you want to allow access depending on permission and node type:
   * @code
   *   $or = $event->getConditions();
   *   if ($event->account()->hasPermission('read letters')) {
   *     $or->condition("$node_table.type", 'letter');
   *   }
   * @endcode
   *
   * @param \Drupal\social_node\Event\NodeQueryAccessEvent $event
   *   The event object.
   *
   * @throws \Exception
   *   Exceptions that could raise during tables join.
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

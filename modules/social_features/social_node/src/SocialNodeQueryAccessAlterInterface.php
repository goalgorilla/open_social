<?php

declare(strict_types=1);

namespace Drupal\social_node;

use Drupal\social_node\Event\NodeQueryAccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Describes how a node access query can be altered in Social.
 *
 *   "Social" gets rid of "hook_node_grands()" for counting access to node
 *   entities in queries. Now it is replaced with query builders.
 *   If you need to add your own query access rules to a node query, rather than
 *   adding "hook_node_grands()", you should add an event subscriber
 *   implementing this interface.
 */
interface SocialNodeQueryAccessAlterInterface extends EventSubscriberInterface {

  /**
   * Short description.
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
  public function alterQueryAccess(NodeQueryAccessEvent $event): void;

}

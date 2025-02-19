<?php

declare(strict_types=1);

namespace Drupal\social_group\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Drupal\social_node\Event\NodeQueryAccessEvent;
use Drupal\social_node\Event\SocialNodeEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Alter query access for nodes with "group" visibility.
 */
class NodeQueryAccessAlterSubscriber implements EventSubscriberInterface {

  /**
   * Constructs QueryAccessSubscriber.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type definition.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface $pluginManager
   *   The group relationship manager.
   */
  public function __construct(
    protected EntityTypeManager $entityTypeManager,
    protected GroupRelationTypeManagerInterface $pluginManager,
  ) {}

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
    $plugin_ids = $this->pluginManager->getPluginIdsByEntityTypeAccess('node');
    if (empty($plugin_ids)) {
      // If no group relationship plugins for "node" entity type is enabled.
      return;
    }

    $account = $event->account();
    $or = $event->getConditions();

    // Make sure we have joined a group membership table.
    $membership_table = $this->ensureMembershipJoin($account, $event);
    $visibility_table = $event->ensureNodeFieldTableJoin('field_content_visibility');

    $or->condition(
      $event->query()->andConditionGroup()
        ->condition("$visibility_table.field_content_visibility_value", 'group')
        ->isNotNull("$membership_table.entity_id")
    );
  }

  /**
   * Ensures the query is joined with the memberships.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   * @param \Drupal\social_node\Event\NodeQueryAccessEvent $event
   *   The event object.
   *
   * @return string
   *   The membership join alias.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function ensureMembershipJoin(AccountInterface $account, NodeQueryAccessEvent $event): string {
    $query = $event->query();

    // Join the memberships of the current user.
    $group_relationship_data_table = $this->entityTypeManager
      ->getDefinition('group_content')
      ->getDataTable();

    foreach ($query->getTables() as $join_info) {
      if (
        $join_info['table'] === $group_relationship_data_table &&
        str_contains((string) $join_info['condition'], 'group_membership')
      ) {
        return $join_info['alias'];
      }
    }

    $group_relationship_table = $this->ensureGroupRelationshipJoin($event);

    return $query->leftJoin(
      $group_relationship_data_table,
      'group_membership',
      "$group_relationship_table.gid=%alias.gid AND %alias.plugin_id='group_membership' AND %alias.entity_id=:account_id",
      [':account_id' => $account->id()]
    );
  }

  /**
   * Ensures the query is joined with the "group_relationship" table.
   *
   * @param \Drupal\social_node\Event\NodeQueryAccessEvent $event
   *   The event object.
   *
   * @return string
   *   The group relationship join alias.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  private function ensureGroupRelationshipJoin(NodeQueryAccessEvent $event): string {
    $query = $event->query();

    $group_relationship_data_table = $this->entityTypeManager
      ->getDefinition('group_content')
      ->getDataTable();

    foreach ($query->getTables() as $join_info) {
      if (
        $join_info['table'] === $group_relationship_data_table &&
        str_contains((string) $join_info['condition'], 'plugin_ids_in_use')
      ) {
        return $join_info['alias'];
      }
    }

    // If the table wasn't joined in any of the previous query builder,
    // we join it here.
    // Join table with group relationship with nodes.
    $node_base_table = $event->ensureNodeDataTable();
    $plugin_ids = $this->pluginManager->getPluginIdsByEntityTypeAccess('node');

    return $query->leftJoin(
      $group_relationship_data_table,
      'group_relationship',
      "$node_base_table.nid=group_relationship.entity_id AND group_relationship.plugin_id IN (:plugin_ids_in_use[])",
      [':plugin_ids_in_use[]' => $plugin_ids]
    );
  }

}

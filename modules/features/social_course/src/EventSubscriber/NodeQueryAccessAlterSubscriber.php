<?php

declare(strict_types=1);

namespace Drupal\social_course\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Drupal\social_node\Event\NodeQueryAccessEvent;
use Drupal\social_node\Event\SocialNodeEvents;
use Drupal\social_node\SocialNodeQueryAccessAlterInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Alters node query access for sections.
 */
class NodeQueryAccessAlterSubscriber implements EventSubscriberInterface, SocialNodeQueryAccessAlterInterface {

  /**
   * Constructs QueryAccessSubscriber.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type definition.
   */
  public function __construct(
    protected EntityTypeManager $entityTypeManager,
    protected GroupRelationTypeManagerInterface $pluginManager,
    protected AccountProxyInterface $currentUser,
  ) {}

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

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    if (!class_exists('\Drupal\social_node\Event\SocialNodeEvents')) {
      return [];
    }

    $events[SocialNodeEvents::NODE_QUERY_ACCESS_ALTER][] = ['alterQueryAccess'];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function alterQueryAccess(NodeQueryAccessEvent $event): void {
    $plugin_ids = $this->pluginManager->getPluginIdsByEntityTypeAccess('node');
    if (empty($plugin_ids)) {
      // If no group relationship plugins for "node" entity type is enabled.
      return;
    }

    $account = $event->account();
    $or = $event->getConditions();

    // Join table with group relationship with nodes.
    $node_base_table = $event->ensureNodeDataTable();
    // Make sure we have joined a group membership table.
    $membership_table = $this->ensureMembershipJoin($account, $event);

    $or->condition(
      $event->query()->andConditionGroup()
        ->condition("$node_base_table.type", 'course_section')
        ->isNotNull("$membership_table.entity_id")
    );

    // Allow access to course_article and course_video if the user
    // is the author.
    $or->condition(
      $event->query()->andConditionGroup()
        ->condition("$node_base_table.type", 'course_article')
        ->condition("$node_base_table.uid", $this->currentUser->id())
    );

    $or->condition(
      $event->query()->andConditionGroup()
        ->condition("$node_base_table.type", 'course_video')
        ->condition("$node_base_table.uid", $this->currentUser->id())
    );
  }

}

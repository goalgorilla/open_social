<?php

declare(strict_types=1);

namespace Drupal\social_group_flexible_group\Hooks;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\hux\Attribute\Alter;
use Drupal\social_group\SocialGroupHelperService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Hooks related to the user group memberships query.
 *
 * @package Drupal\social_group_flexible_group\Hooks
 */
final class UserGroupMembershipsQuery implements ContainerInjectionInterface {

  /**
   * UserGroupMembershipsQuery constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   */
  public function __construct(
    protected Connection $connection,
    protected AccountInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('database'),
      $container->get('current_user'),
    );
  }

  /**
   * Alters user group membership queries for GraphQL groupMemberships.
   *
   * Restricts user group membership queries to viewer-visible groups.
   *
   * @param \Drupal\Core\Database\Query\AlterableInterface $query
   *   The query to alter.
   *
   * @see \hook_query_TAG_alter()
   * @see UserGroupMembershipsQueryHelper::getQuery()
   * @see group_query_entity_query_alter()
   * @see social_group_flexible_group_group_access()
   */
  #[Alter('query_user_group_memberships_visible_groups')]
  public function alterUserGroupMembershipsVisibleGroups(AlterableInterface $query): void {
    if (!$query instanceof SelectInterface) {
      return;
    }

    $gid_field = $this->resolveGroupRelationshipGidField($query);
    if ($gid_field === NULL) {
      $query->alwaysFalse();
      return;
    }

    // Mirrors SearchApiGroupAccess, views visibility alter, and
    // social_group_flexible_group_group_access(): no view restrictions apply.
    if ($this->currentUser->hasPermission('manage all groups')) {
      return;
    }

    $group_subquery = $this->connection->select('groups', 'g');
    $group_subquery->fields('g', ['id']);
    $group_subquery->innerJoin('groups_field_data', 'gfd', 'g.id = gfd.id AND gfd.default_langcode = 1');
    $group_subquery->condition('gfd.type', 'flexible_group');
    $group_subquery->addTag('entity_query');
    $group_subquery->addTag('group_access');
    $group_subquery->addMetaData('entity_type', 'group');
    $group_subquery->addMetaData('op', 'view');

    // Process entity query access.
    group_query_entity_query_alter($group_subquery);

    // Process group visibility access.
    $group_subquery->condition('g.id', $this->visibleFlexibleGroupIdsSubquery($this->currentUser), 'IN');

    $query->condition($gid_field, $group_subquery, 'IN');
  }

  /**
   * Resolves the gid column on the group membership query.
   *
   * @return string|null
   *   Qualified gid field name, or NULL when not present.
   */
  private function resolveGroupRelationshipGidField(SelectInterface $query): ?string {
    foreach ($query->getTables() as $alias => $table) {
      if ($table['table'] === 'group_relationship_field_data') {
        return "$alias.gid";
      }
    }

    return NULL;
  }

  /**
   * Builds a subquery of flexible group IDs visible to the account.
   *
   * Mirrors the checks in social_group_flexible_group_group_access().
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account whose visibility permissions apply.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   A subquery returning group IDs.
   */
  private function visibleFlexibleGroupIdsSubquery(AccountInterface $account): SelectInterface {
    $sub_query = $this->connection->select('group__field_flexible_group_visibility', 'group_visibility');
    $sub_query->addField('group_visibility', 'entity_id', 'id');
    $sub_query->condition('group_visibility.bundle', 'flexible_group');

    $allowed_visibilities = SocialGroupHelperService::getAvailableVisibilities();
    $or = $sub_query->orConditionGroup();
    foreach ($allowed_visibilities as $visibility) {
      if (!$account->hasPermission("view $visibility flexible_group group")) {
        continue;
      }

      $visibility_condition = $sub_query->andConditionGroup()
        ->condition('group_visibility.field_flexible_group_visibility_value', $visibility);

      if ($visibility === 'members') {
        $memberships = $this->connection->select('group_relationship_field_data', 'gm');
        $memberships->addField('gm', 'gid', 'entity_id');
        $memberships->condition('gm.entity_id', $account->id());
        $memberships->condition('gm.group_type', 'flexible_group');
        $memberships->condition('gm.plugin_id', 'group_membership');
        $visibility_condition->condition('group_visibility.entity_id', $memberships, 'IN');
      }

      $or->condition($visibility_condition);
    }

    $conditions = $or->conditions();
    if (!$conditions || !Element::children($conditions)) {
      $or->alwaysFalse();
    }
    $sub_query->condition($or);

    return $sub_query;
  }

}

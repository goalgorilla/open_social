<?php

namespace Drupal\social_group_flexible_group\Plugin\views\filter;

use Drupal\Core\Database\Query\Condition;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Views;

/**
 * Filter by node access based on Group membership.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("flexible_group_node_access")
 */
class FlexibleGroupNodeAccess extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {}

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * See _node_access_where_sql() for a non-views query based implementation.
   */
  public function query() {
    $account = $this->view->getUser();
    $group_access = NULL;
    if (!$account->hasPermission('bypass node access')) {
      // Ensure we check for group content.
      // Join node table(s).
      $configuration = [
        'left_table' => 'node_field_data',
        'left_field' => 'nid',
        'table' => 'group_content_field_data',
        'field' => 'entity_id',
        'operator' => '=',
      ];

      $join = Views::pluginManager('join')->createInstance('standard', $configuration);
      $this->query->addRelationship('membership', $join, 'node_field_data');

      // Add extra condition for Group Membership
      // related check in Flexible groups.
      $group_memberships = \Drupal::service('social_group.helper_service')->getAllGroupsForUser($account->id());
      if (!empty($group_memberships) && !$account->isAnonymous()) {
        // OR content is GROUP.
        $group_access = new Condition('OR');
        $group_access->condition('membership.gid', $group_memberships, 'IN');
      }

      // Also check for Open / Public within groups.
      $configuration = [
        'left_table' => 'node_field_data',
        'left_field' => 'nid',
        'table' => 'node__field_content_visibility',
        'field' => 'entity_id',
        'operator' => '=',
      ];

      $join = Views::pluginManager('join')->createInstance('standard', $configuration);
      $this->query->addRelationship('field_visibility_relationship', $join, 'node__field_content_visibility');

      $group_visible = new Condition('OR');
      $group_visible->condition('field_content_visibility_value', 'public');
      if (!$account->isAnonymous()) {
        $group_visible->condition('field_content_visibility_value', 'community');
      }
      if ($group_access !== NULL) {
        $group_visible->condition($group_access);
      }

      // And we should check for open / public.
      $this->query->addWhere('visibility', $group_visible);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();

    $contexts[] = 'user.node_grants:view';

    return $contexts;
  }

}

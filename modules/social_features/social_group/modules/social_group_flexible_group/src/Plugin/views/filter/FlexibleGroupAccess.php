<?php

namespace Drupal\social_group_flexible_group\Plugin\views\filter;

use Drupal\Core\Database\Query\Condition;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Views;

/**
 * Filter by groups access.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("flexible_group_access")
 */
class FlexibleGroupAccess extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
  }

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $account = $this->view->getUser();
    $group_access = NULL;
    if (!$account->hasPermission('administer group') && !$account->hasPermission('bypass group access')) {
      // Ensure we check for group content.
      // Join node table(s).
      $configuration = [
        'left_table' => 'groups_field_data',
        'left_field' => 'id',
        'table' => 'group__field_flexible_group_visibility',
        'field' => 'entity_id',
        'operator' => '=',
      ];

      $join = Views::pluginManager('join')
        ->createInstance('standard', $configuration);
      $this->query->addRelationship('group_visibility', $join, 'groups_field_data');

      // Add extra condition for Group Membership
      // related check in Flexible groups.
      $group_memberships = \Drupal::service('social_group.helper_service')
        ->getAllGroupsForUser($account->id());
      if (!empty($group_memberships) && !$account->isAnonymous()) {
        $configuration = [
          'left_table' => 'groups_field_data',
          'left_field' => 'id',
          'table' => 'group_content_field_data',
          'field' => 'gid',
          'operator' => '=',
        ];

        $join = Views::pluginManager('join')
          ->createInstance('standard', $configuration);
        $this->query->addRelationship('group_content', $join, 'groups_field_data');

        $group_access = new Condition('OR');
        $group_access->condition('group_content.gid', $group_memberships, 'IN');
      }

      $group_visible = new Condition('OR');
      $group_visible->condition('group_visibility.field_flexible_group_visibility_value', 'public');
      if (!$account->isAnonymous()) {
        $group_visible->condition('group_visibility.field_flexible_group_visibility_value', 'community');
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

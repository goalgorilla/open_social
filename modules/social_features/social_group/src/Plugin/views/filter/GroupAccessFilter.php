<?php

namespace Drupal\social_group\Plugin\views\filter;

use Drupal\Core\Database\Query\Condition;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Views;

/**
 * Filter by groups access.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("group_access")
 */
class GroupAccessFilter extends FilterPluginBase {

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
      $group_visible->condition('groups_field_data.type', 'public_group');

      if (!$account->isAnonymous()) {
        $group_visible->condition('groups_field_data.type', 'open_group');
      }
      if (\Drupal::service('module_handler')
        ->moduleExists('social_group_flexible_group')) {
        $this->filterFlexibleGroups($group_visible, $account);
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

  private function filterFlexibleGroups(&$condition, $account) {
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
    $condition->condition('group_visibility.field_flexible_group_visibility_value', 'public');
    if (!$account->isAnonymous()) {
      $condition->condition('group_visibility.field_flexible_group_visibility_value', 'community');
    }
  }

}

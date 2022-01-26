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
    if (!$account->hasPermission('administer group') && !$account->hasPermission('bypass group access')) {
      $group_access = $this->filterGroupsOnMembership($account);

      $group_visible = new Condition('OR');
      $group_visible->condition('groups_field_data.type', 'public_group');

      if (!$account->isAnonymous()) {
        $group_visible->condition('groups_field_data.type', 'open_group');
      }
      if (\Drupal::service('module_handler')
        ->moduleExists('social_group_flexible_group')) {
        $flexible_groups_visible = $this->filterFlexibleGroups($account->isAnonymous());
        $group_visible->condition($flexible_groups_visible);
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

    $contexts[] = 'user';

    return $contexts;
  }

  /**
   * Filter groups on membership for given account.
   *
   * @param $account
   *   The account to check memberships for.
   *
   * @return \Drupal\Core\Database\Query\Condition|null
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function filterGroupsOnMembership($account): ?Condition {
    $group_memberships = \Drupal::service('social_group.helper_service')
      ->getAllGroupsForUser($account->id());
    // If user is part of groups and is not anonymous.
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

      $group_membership_access = new Condition('OR');
      $group_membership_access->condition('group_content.gid', $group_memberships, 'IN');
      return $group_membership_access;
    }
    return NULL;
  }

  /**
   * Filter flexible groups.
   *
   * @param $anonymous
   *   Whether or not the current user is anonymous.
   *
   * @return void
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function filterFlexibleGroups($anonymous): Condition {
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
    $flexible_groups_visible = new Condition('OR');
    $flexible_groups_visible->condition('group_visibility.field_flexible_group_visibility_value', 'public');
    if (!$anonymous) {
      $flexible_groups_visible->condition('group_visibility.field_flexible_group_visibility_value', 'community');
    }
    return $flexible_groups_visible;
  }

}

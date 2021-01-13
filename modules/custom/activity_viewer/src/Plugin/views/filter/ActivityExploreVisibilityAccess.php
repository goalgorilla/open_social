<?php

namespace Drupal\activity_viewer\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Views;

/**
 * Filters activity based on visibility settings for Explore.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("activity_explore_visibility_access")
 */
class ActivityExploreVisibilityAccess extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * Make sure we render content in explore which we should see, but don't.
   *
   * Because what differentiates Explore from a Stream is in Explore you see
   * content also from unrelated content. So content from groups you are
   * not a member of. So in this case we can:
   * 1. Show content from groups you are not a member off.
   * Only when that content visibility is set to community or public for LU.
   * 2. OR the content is NOT placed in a group at all
   * 3. OR the content is not a Node, we don't care about that here.
   * This translates to code as follows:
   */
  public function query() {
    // Create defaults.
    $account = $this->view->getUser();
    $explore_wrapper = db_and();
    $explore_or = db_or();

    // Joins from activity to node.
    $configuration = [
      'left_table' => 'activity__field_activity_entity',
      'left_field' => 'field_activity_entity_target_id',
      'table' => 'node_field_data',
      'field' => 'nid',
      'operator' => '=',
      'extra' => [
        0 => [
          'left_field' => 'field_activity_entity_target_type',
          'value' => 'node',
        ],
      ],
    ];
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    $this->query->addRelationship('node_field_data', $join, 'node_field_data');
    // And from node to it's content_visibility field.
    $configuration = [
      'left_table' => 'node_field_data',
      'left_field' => 'nid',
      'table' => 'node__field_content_visibility',
      'field' => 'entity_id',
      'operator' => '=',
    ];
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    $this->query->addRelationship('node__field_content_visibility', $join, 'node__field_content_visibility');

    // Let's build our condition.
    // Either it's not a node so we don't care, the other filters will
    // take care of it. Look at ActivityPostVisibilityAccess.
    $node_condition = db_or();
    $node_condition->condition('activity__field_activity_entity.field_activity_entity_target_type', 'node', '!=');

    // OR for LU it's a node and it doesn't have group member visibility.
    // so only Community and Public is shown.
    if ($account->isAuthenticated()) {
      // Remove all content from groups I am a member of.
      $nodes_not_in_groups = db_or();
      if ($my_groups = \Drupal::service('social_group.helper_service')
        ->getAllGroupsForUser($account->id())) {
        $nodes_not_in_groups->condition(db_and()
          ->condition('activity__field_activity_recipient_group.field_activity_recipient_group_target_id', $my_groups, 'NOT IN')
          ->condition('node__field_content_visibility.field_content_visibility_value', 'group', '!='));
      }

      // Include all the content which is posted in groups but with
      // visibility either community or public.
      $nodes_not_in_groups->condition(db_and()
        ->isNotNull('activity__field_activity_recipient_group.field_activity_recipient_group_target_id')
        ->condition('node__field_content_visibility.field_content_visibility_value', 'group', '!='));

      // This will include the nodes that has not been posted in any group.
      $nodes_not_in_groups->condition(db_and()
        ->isNull('activity__field_activity_recipient_group.field_activity_recipient_group_target_id')
        ->condition('node__field_content_visibility.field_content_visibility_value', 'group', '!='));

      $nodes_not_in_groups->condition($node_condition);
    }
    else {
      // OR we remove activities related to nodes with community and group
      // visibility for AN.
      $nodes_not_in_groups = db_or();
      $nodes_not_in_groups->condition(db_and()
        ->condition('node__field_content_visibility.field_content_visibility_value', 'community', '!=')
        ->condition('node__field_content_visibility.field_content_visibility_value', 'group', '!='));
      $nodes_not_in_groups->condition($node_condition);
    }
    $explore_or->condition($nodes_not_in_groups);

    // So we add a new and wrapper which states.
    // Or we don't care about non nodes (so posts and comments are shown)
    // Or we do care, and we only show content not in my groups,
    // and those I have access to based on visibility.
    $explore_wrapper->condition($explore_or);
    // Add a new Where clause.
    $this->query->addWhere('explore', $explore_wrapper);
  }

}

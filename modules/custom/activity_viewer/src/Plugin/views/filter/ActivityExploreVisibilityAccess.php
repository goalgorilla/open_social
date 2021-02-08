<?php

namespace Drupal\activity_viewer\Plugin\views\filter;

use Drupal\Core\Database\Query\Condition;
use Drupal\social_group\SocialGroupHelperService;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filters activity based on visibility settings for Explore.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("activity_explore_visibility_access")
 */
class ActivityExploreVisibilityAccess extends FilterPluginBase {

  /**
   * The group helper.
   *
   * @var \Drupal\social_group\SocialGroupHelperService
   */
  protected $groupHelper;

  /**
   * Constructs a Handler object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\social_group\SocialGroupHelperService $group_helper
   *   The group helper.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SocialGroupHelperService $group_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->groupHelper = $group_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('social_group.helper_service')
    );
  }

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
    $explore_wrapper = new Condition('AND');
    $explore_or = new Condition('OR');

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
    $node_condition = new Condition('OR');
    $node_condition->condition('activity__field_activity_entity.field_activity_entity_target_type', 'node', '!=');

    // OR for LU it's a node and it doesn't have group member visibility.
    // so only Community and Public is shown.
    if ($account->isAuthenticated()) {
      // Remove all content from groups I am a member of.
      $nodes_not_in_groups = new Condition('OR');
      $new_and = new Condition('AND');
      if ($my_groups = $this->groupHelper
        ->getAllGroupsForUser($account->id())) {
        $nodes_not_in_groups->condition($new_and
          ->condition('activity__field_activity_recipient_group.field_activity_recipient_group_target_id', $my_groups, 'NOT IN')
          ->condition('node__field_content_visibility.field_content_visibility_value', 'group', '!='));
      }

      // Include all the content which is posted in groups but with
      // visibility either community or public.
      $new_and = new Condition('AND');
      $nodes_not_in_groups->condition($new_and
        ->isNotNull('activity__field_activity_recipient_group.field_activity_recipient_group_target_id')
        ->condition('node__field_content_visibility.field_content_visibility_value', 'group', '!='));

      // This will include the nodes that has not been posted in any group.
      $new_and = new Condition('AND');
      $nodes_not_in_groups->condition($new_and
        ->isNull('activity__field_activity_recipient_group.field_activity_recipient_group_target_id')
        ->condition('node__field_content_visibility.field_content_visibility_value', 'group', '!='));

      $nodes_not_in_groups->condition($node_condition);
    }
    else {
      // OR we remove activities related to nodes with community and group
      // visibility for AN.
      $nodes_not_in_groups = new Condition('OR');
      $new_and = new Condition('AND');
      $nodes_not_in_groups->condition($new_and
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

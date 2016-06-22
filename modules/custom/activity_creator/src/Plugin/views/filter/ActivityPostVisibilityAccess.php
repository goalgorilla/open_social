<?php
/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\views\filter\ActivityPostVisibilityAccess.
 */

namespace Drupal\activity_creator\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Views;

/**
 * Filters activity based on visibility settings.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("activity_post_visibility_access")
 */
class ActivityPostVisibilityAccess extends FilterPluginBase {

  /**
   * Not exposable.
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * Currently use similar access as for the entity.
   *
   * Probably want to extend this to entity access based on the node grant
   * system when this is implemented.
   * See https://www.drupal.org/node/777578
   */
  public function query() {
    $account = $this->view->getUser();
    /* @var \Drupal\views\Plugin\views\query\Sql $this->query */

    // Add tables and joins.
    $this->query->addTable('activity__field_activity_entity');

    $configuration = array(
      'left_table' => 'activity__field_activity_entity',
      'left_field' => 'field_activity_entity_target_id',
      'table' => 'post',
      'field' => 'id',
      'operator' => '=',
    );
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    $this->query->addRelationship('post', $join, 'activity__field_activity_entity');

    $configuration = array(
      'left_table' => 'post',
      'left_field' => 'id',
      'table' => 'post__field_visibility',
      'field' => 'entity_id',
      'operator' => '=',
    );
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    $this->query->addRelationship('post__field_visibility', $join, 'post__field_visibility');

    // Join node table.
    $configuration = array(
      'left_table' => 'activity__field_activity_entity',
      'left_field' => 'field_activity_entity_target_id',
      'table' => 'node_access',
      'field' => 'nid',
      'operator' => '=',
    );
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    $this->query->addRelationship('node_access', $join, 'asdfasfd');

    // Add queries.
    $and_wrapper = db_and();
    $or = db_or();

    $node_access = db_and();
    $node_access->condition('activity__field_activity_entity.field_activity_entity_target_type', 'node', '=');
    $node_access_grants = node_access_grants('view', $account);
    $grants = db_or();

    foreach ($node_access_grants as $realm => $gids) {
      if (!empty($gids)) {
        $and = db_and();
        $grants->condition($and
          ->condition('node_access.gid', $gids, 'IN')
          ->condition('node_access.realm', $realm)
        );
      }
    }
    $node_access->condition($grants);
    $or->condition($node_access);

    $post_access = db_and();
    $post_access->condition('activity__field_activity_entity.field_activity_entity_target_type', 'post', '=');
    if (!$account->hasPermission('view public posts')) {
      $post_access->condition('post__field_visibility.field_visibility_value', '1', '!=');
    }
    if (!$account->hasPermission('view community posts')) {
      $post_access->condition('post__field_visibility.field_visibility_value', '2', '!=');
      // Also do not show recipient posts (e.g. on groups).
      $post_access->condition('post__field_visibility.field_visibility_value', '0', '!=');
    }

    $or->condition($post_access);

    $and_wrapper->condition($or);

    $this->query->addWhere('visibility', $and_wrapper);

  }

}

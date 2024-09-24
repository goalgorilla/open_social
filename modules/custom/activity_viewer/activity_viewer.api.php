<?php

/**
 * @file
 * Hooks provided by the Activity Viewer module.
 */

use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the query in filter for node visibility in activities.
 *
 * @param \Drupal\Core\Database\Query\SelectInterface $query
 *   Query from the filter.
 * @param \Drupal\Core\Session\AccountInterface $user
 *   Current user.
 */
function hook_activity_viewer_available_nodes_query_alter(SelectInterface $query, AccountInterface $user) {

}

/**
 * Alter the query in filter for post visibility in activities.
 *
 * @param \Drupal\Core\Database\Query\SelectInterface $query
 *   Query from the filter.
 * @param \Drupal\Core\Session\AccountInterface $user
 *   Current user.
 */
function hook_activity_viewer_available_posts_query_alter(SelectInterface $query, AccountInterface $user) {

}

/**
 * Alter the query in filter.
 *
 * Sometimes we need to change visibility for custom entities in activities.
 *
 * @param \Drupal\Core\Database\Query\SelectInterface $filter_query
 *   The query from the filter.
 * @param \Drupal\Core\Database\Query\ConditionInterface $or_condition
 *   The top level or condition of the query.
 * @param \Drupal\Core\Session\AccountInterface $user
 *   The current user.
 */
function hook_activity_viewer_personalized_homepage_query_alter(SelectInterface $filter_query, ConditionInterface $or_condition, AccountInterface $user) {
  // Prefer not to use this hook, we aim to remove it.
}

/**
 * @} End of "addtogroup hooks".
 */

<?php

/**
 * @file
 * Hooks provided by the Activity Viewer module.
 */

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
function hook_activity_viewer_available_nodes_query_alter(\Drupal\Core\Database\Query\SelectInterface $query, \Drupal\Core\Session\AccountInterface $user) {

}

/**
 * Alter the query in filter for post visibility in activities.
 *
 * @param \Drupal\Core\Database\Query\SelectInterface $query
 *   Query from the filter.
 * @param \Drupal\Core\Session\AccountInterface $user
 *   Current user.
 */
function hook_activity_viewer_available_posts_query_alter(\Drupal\Core\Database\Query\SelectInterface $query, \Drupal\Core\Session\AccountInterface $user) {

}

/**
 * @} End of "addtogroup hooks".
 */

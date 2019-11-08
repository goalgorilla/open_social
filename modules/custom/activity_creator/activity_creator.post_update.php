<?php

/**
 * @file
 * Contains post update hook implementations.
 */

/**
 * Implements hook_post_update_name().
 *
 * Migrate all the activity information to new table.
 * This is necessary as we have changed the logic of reading notifications
 * and marking them as seen. So, we have migrate the existing activity entries
 * to new table so as to avoid any missing notifications by users.
 *
 * @throws \Exception
 */
function activity_creator_post_update_8001_one_to_many_activities(&$sandbox) {
  // Fetching amount of data we need to process.
  // Runs only once per update.
  $connection = \Drupal::database();
  if (!isset($sandbox['total'])) {
    // Get count of all the necessary fields information from current database.
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $connection->select('activity__field_activity_recipient_user', 'aur');
    $query->join('activity__field_activity_status', 'asv', 'aur.entity_id = asv.entity_id');
    $number_of_activities = $query
      ->fields('aur', ['entity_id', 'field_activity_recipient_user_target_id'])
      ->fields('asv', ['field_activity_status_value'])
      ->countQuery()
      ->execute()->fetchField();

    // Write total of entities need to be processed to $sandbox.
    $sandbox['total'] = $number_of_activities;
    // Initiate default value for current processing of element.
    $sandbox['current'] = 0;
  }

  // Do not continue if no entities are found.
  if (empty($sandbox['total'])) {
    $sandbox['#finished'] = 1;
    return t('No activities data to be processed.');
  }

  // Get all the necessary fields information from current database.
  /** @var \Drupal\Core\Database\Query\Select $query */
  $query = $connection->select('activity__field_activity_recipient_user', 'aur');
  $query->join('activity__field_activity_status', 'asv', 'aur.entity_id = asv.entity_id');
  $query->addField('aur', ' field_activity_recipient_user_target_id', 'uid');
  $query->addField('aur', 'entity_id', 'aid');
  $query->addField('asv', 'field_activity_status_value', 'status');
  $query->condition('field_activity_recipient_user_target_id', 0, '!=');
  $query->range($sandbox['current'], 5000);

  // Prepare the insert query and execute using previous select query.
  $connection->insert('activity_notification_status')->from($query)->execute();

  // Increment currently processed entities.
  // Check if current starting point is less than our range selection.
  if ($sandbox['total'] - $sandbox['current'] > 5000) {
    $sandbox['current'] += 5000;
  }
  else {
    // If we have less number of results to process, we increment by difference.
    $sandbox['current'] += ($sandbox['total'] - $sandbox['current']);
  }

  // The batch will finish when '#finished' will become '1'.
  $sandbox['#finished'] = ($sandbox['current'] / $sandbox['total']);
  // Print some progress.
  return t('@count activities data has been migrated to activity_notification_table.', ['@count' => $sandbox['current']]);
}

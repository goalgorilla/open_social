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
  if (!isset($sandbox['total'])) {
    // Get count of all the necessary fields information from current database.
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = \Drupal::database()->select('activity__field_activity_recipient_user', 'aur');
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
  $query = \Drupal::database()->select('activity__field_activity_recipient_user', 'aur');
  $query->join('activity__field_activity_status', 'asv', 'aur.entity_id = asv.entity_id');
  $results = $query
    ->fields('aur', ['entity_id', 'field_activity_recipient_user_target_id'])
    ->fields('asv', ['field_activity_status_value'])
    ->range($sandbox['current'], 1000)
    ->execute()->fetchAll();

  // Prepare the insert query.
  $query = \Drupal::database()->insert('activity_notification_status')
    ->fields([
      'aid',
      'uid',
      'status',
    ]);

  // Insert the information in activity_notification_status table.
  foreach ($results as $result) {
    $query->values([
      'aid' => $result->entity_id,
      'uid' => $result->field_activity_recipient_user_target_id,
      'status' => $result->field_activity_status_value,
    ]);

    // Increment currently processed entities.
    $sandbox['current']++;
  }

  // Execute the query with all values;.
  $query->execute();

  // The batch will finish when '#finished' will become '1'.
  $sandbox['#finished'] = ($sandbox['current'] / $sandbox['total']);
  // Print some progress.
  return t('@count activities data has been migrated to activity_notification_table.', ['@count' => $sandbox['current']]);
}

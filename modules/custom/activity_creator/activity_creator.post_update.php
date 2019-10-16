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
    // Get all the necessary fields information from current database.
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = \Drupal::database()->select('activity__field_activity_recipient_user', 'aur');
    $query->join('activity__field_activity_status', 'asv', 'aur.entity_id = asv.entity_id');
    $results = $query
      ->fields('aur', ['entity_id', 'field_activity_recipient_user_target_id'])
      ->fields('asv', ['field_activity_status_value'])
      ->execute()->fetchAll();

    // Write total of entities need to be processed to $sandbox.
    $sandbox['total'] = count($results);
    // Initiate default value for current processing № of element.
    $sandbox['current'] = 0;
    // Store all data in sandbox to be processed in chunks.
    $sandbox['activity-ids'] = $results;
  }

  // Do not continue if no entities are found.
  if (empty($sandbox['total'])) {
    $sandbox['#finished'] = 1;
    return t('No activities data to be processed.');
  }

  // How much entities can be processed per batch.
  $limit = 50;
  // Take out a chunk of activity ids for processing in current batch run.
  $results = array_slice($sandbox['activity-ids'], $sandbox['current'], $limit);

  // Insert the information in activity_notification_status table.
  foreach ($results as $result) {
    \Drupal::database()->insert('activity_notification_status')
      ->fields([
        'aid',
        'uid',
        'status',
      ], [
        $result->entity_id,
        $result->field_activity_recipient_user_target_id,
        $result->field_activity_status_value,
      ])
      ->execute();
    // Increment currently processed entities.
    $sandbox['current']++;
  }

  // The batch will finish when '#finished' will become '1'.
  $sandbox['#finished'] = ($sandbox['current'] / $sandbox['total']);
  // Print some progress.
  return t('@count activities data has been migrated to activity_notification_table.', ['@count' => $sandbox['current']]);
}

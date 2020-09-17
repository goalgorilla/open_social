<?php

/**
 * @file
 * Contains post update hook implementations.
 */

use Drupal\activity_creator\ActivityInterface;
use Drupal\Core\Site\Settings;

/**
 * Migrate all the activity status information to new table.
 *
 * This is necessary as we have changed the logic of reading notifications
 * and marking them as seen. So, we have migrate the existing activity entries
 * to new table so as to avoid any missing notifications by users.
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
  $query->addField('aur', 'field_activity_recipient_user_target_id', 'uid');
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

/**
 * Remove activities notification status if related entity not exist.
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function activity_creator_post_update_8802_remove_activities_with_no_related_entities(&$sandbox) {
  // Fetching amount of data we need to process.
  // Runs only once per update.
  $database = \Drupal::database();
  if (!isset($sandbox['total'])) {
    // Get count of all the necessary fields information from current database.
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $database->select('activity_notification_status', 'ans');
    $number_of_activities = $query->countQuery()->execute()->fetchField();

    // Write total of entities need to be processed to $sandbox.
    $sandbox['total'] = $number_of_activities;
    // Initiate default value for current processing of element.
    $sandbox['current'] = 0;

    // Get activity IDs.
    /** @var \Drupal\Core\Database\Query\Select $query */
    $activity_ids = $database->select('activity_notification_status', 'ans')
      ->fields('ans', ['aid'])
      ->execute()
      ->fetchCol();

    // We pass all the ids in sandbox to be processed in each batch.
    // We will choose a chunk of ids from this ids and use $sanbox['current']
    // as offset. We do this to ensure all the ids are processed because we are
    // deleting the data from the same table we are choosing from.
    $sandbox['activities_id'] = $activity_ids;
  }

  // Do not continue if no entities are found.
  if (empty($sandbox['total'])) {
    $sandbox['#finished'] = 1;
    return t('No activities data to be processed.');
  }

  // Activities per one batch operation.
  $activities_per_batch = Settings::get('activity_update_batch_size', 10000);
  // Get activity storage.
  $activity_storage = $activity = \Drupal::entityTypeManager()
    ->getStorage('activity');

  // Choose chunk of array to be processed.
  $activity_ids = array_slice($sandbox['activities_id'], $sandbox['current'], $activities_per_batch);

  // Prepare the array of ids for deletion.
  foreach ($activity_ids as $aid) {
    /** @var \Drupal\activity_creator\ActivityInterface $activity */
    $activity = $activity_storage->load($aid);

    // Add invalid ids for deletion.
    if (!$activity instanceof ActivityInterface) {
      $aids_for_delete[] = $aid;
    }
    // Add not required $activity.
    elseif (is_null($activity->getRelatedEntity())) {
      $aids_for_delete[] = $aid;
      $activities_for_delete[$aid] = $activity;
    }
  }

  // Remove notifications.
  if (!empty($aids_for_delete)) {
    \Drupal::service('activity_creator.activity_notifications')
      ->deleteNotificationsbyIds($aids_for_delete);
  }

  // Delete not required activity entities.
  if (!empty($activities_for_delete)) {
    $activity_storage->delete($activities_for_delete);
  }

  // Increment currently processed entities.
  // Check if current starting point is less than our range selection.
  if ($sandbox['total'] - $sandbox['current'] > $activities_per_batch) {
    $sandbox['current'] += $activities_per_batch;
  }
  else {
    // If we have less number of results to process, we increment by difference.
    $sandbox['current'] += ($sandbox['total'] - $sandbox['current']);
  }

  // The batch will finish when '#finished' will become '1'.
  $sandbox['#finished'] = ($sandbox['current'] / $sandbox['total']);
  // Print some progress.
  return t('@count activities data has been cleaned up.', ['@count' => $sandbox['current']]);
}

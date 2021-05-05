<?php

/**
 * @file
 * Contains post update hook implementations.
 */

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
 * Remove orphaned activities notification status.
 */
function activity_creator_post_update_8802_remove_orphaned_activities(&$sandbox) {
  $database = \Drupal::database();

  // On the first run, we gather all of our initial
  // data as well as initialize all of our sandbox variables to be used in
  // managing the future batch requests.
  if (!isset($sandbox['activities_id'])) {
    // We start the batch by running some SELECT queries up front
    // as concisely as possible. The results of these expensive queries
    // will be cached by the Batch API so we do not have to look up
    // this data again during each iteration of the batch.
    // Get all the activity ids from our notification table.
    $activity_notification_ids = $database->select('activity_notification_status', 'ans')->fields('ans', ['aid'])->execute()->fetchCol();

    // Get activity ids from entity table.
    $activity_ids = $database->select('activity', 'aid')->fields('aid', ['id'])->execute()->fetchCol();

    // Now we initialize the sandbox variables.
    // These variables will persist across the Batch API’s subsequent calls
    // to our update hook, without us needing to make those initial
    // expensive SELECT queries above ever again.
    // 'count' is the number of total records we’ll be processing.
    $sandbox['count'] = 0;

    // We take store a diff of both the results which will contain the result
    // of activity ids which are not present in system anymore. We will
    // remove them in batch later.
    if (!empty($activity_notification_ids) && !empty($activity_ids)) {
      $sandbox['activities_id'] = array_diff($activity_notification_ids, $activity_ids);

      $sandbox['count'] = count($sandbox['activities_id']);
    }

    // If 'count' is empty, we have nothing to process.
    if (empty($sandbox['count'])) {
      $sandbox['#finished'] = 1;
      return;
    }

    // 'progress' will represent the current progress of our processing.
    $sandbox['progress'] = 0;

    // 'activities_per_batch' is a custom amount that we’ll use to limit
    // how many activities we’re processing in each batch.
    // The variables value can be declared in settings file of Drupal.
    $sandbox['activities_per_batch'] = Settings::get('activity_update_batch_size', 5000);
  }

  // Initialization code done.
  // The following code will always run:
  // both during the first run AND during any subsequent batches.
  // Remove the entries from activity_notification_status table which have
  // activity id that doest not exists any more.
  \Drupal::service('activity_creator.activity_notifications')
    ->deleteNotificationsbyIds(array_splice($sandbox['activities_id'], 0, 5000));

  // Calculates current batch range.
  $range_end = $sandbox['progress'] + $sandbox['activities_per_batch'];
  if ($range_end > $sandbox['count']) {
    $range_end = $sandbox['count'];
  }

  // Update the batch variables to track our progress.
  $sandbox['progress'] = $range_end;

  // We can calculate our current progress via a mathematical fraction.
  $progress_fraction = $sandbox['progress'] / $sandbox['count'];

  // Drupal’s Batch API will stop executing our update hook as soon as
  // $sandbox['#finished'] == 1 (viz., it evaluates to TRUE).
  $sandbox['#finished'] = empty($sandbox['activities_id']) ? 1 : ($sandbox['count'] - count($sandbox['activities_id'])) / $sandbox['count'];
}

/**
 * Remove activities notification status if it's related entity not exist.
 */
function activity_creator_post_update_8803_remove_activities_with_no_related_entities(&$sandbox) {
  $database = \Drupal::database();

  if (!isset($sandbox['activities_id'])) {
    // Get activity ids from entity table.
    $activity_ids = $database->select('activity', 'aid')->fields('aid', ['id'])->execute()->fetchCol();

    // Get activity ids from activity__field_activity_entity table.
    // This table contains data of field_activity_entity which tells us about
    // any related entity to an activity.
    $afce_ids = $database->select('activity__field_activity_entity', 'afce')
      ->fields('afce', ['entity_id'])
      ->execute()->fetchCol();

    // 'count' is the number of total records we’ll be processing.
    $sandbox['count'] = 0;

    // We take store a diff of both the results which will contain the result
    // of activity ids which doesn't have valid referenced entities any more.
    // We will remove them in batch later. Also, we are only checking
    // $activity_id to be not empty because $afce_ids is null, that means we
    // shall remove all activities as none of them will have valid referenced
    // entity.
    if (!empty($activity_ids)) {
      $sandbox['activities_id'] = array_diff($activity_ids, $afce_ids);

      $sandbox['count'] = count($sandbox['activities_id']);
    }

    // If 'count' is empty, we have nothing to process.
    if (empty($sandbox['count'])) {
      $sandbox['#finished'] = 1;
      return;
    }

    // 'progress' will represent the current progress of our processing.
    $sandbox['progress'] = 0;

    // 'activities_per_batch' is a custom amount that we’ll use to limit
    // how many activities we’re processing in each batch.
    // The variables value can be declared in settings file of Drupal.
    $sandbox['activities_per_batch'] = Settings::get('activity_update_batch_size', 100);
  }

  // Extract activity ids for deletion.
  $aids_for_delete = array_splice($sandbox['activities_id'], 0, $sandbox['activities_per_batch']);
  // Now let’s remove the activities with no related entities.
  $storage = \Drupal::entityTypeManager()->getStorage('activity');
  $activities = $storage->loadMultiple($aids_for_delete);
  $storage->delete($activities);

  // Remove entries from activity_notification_table.
  $activity_notification_service = \Drupal::service('activity_creator.activity_notifications');
  $activity_notification_service->deleteNotificationsbyIds($aids_for_delete);

  // Calculates current batch range.
  $range_end = $sandbox['progress'] + $sandbox['activities_per_batch'];
  if ($range_end > $sandbox['count']) {
    $range_end = $sandbox['count'];
  }

  // Update the batch variables to track our progress.
  $sandbox['progress'] = $range_end;

  // We can calculate our current progress via a mathematical fraction.
  $progress_fraction = $sandbox['progress'] / $sandbox['count'];

  // Tell the Batch API about status of this process.
  $sandbox['#finished'] = empty($sandbox['activities_id']) ? 1 : ($sandbox['count'] - count($sandbox['activities_id'])) / $sandbox['count'];
}

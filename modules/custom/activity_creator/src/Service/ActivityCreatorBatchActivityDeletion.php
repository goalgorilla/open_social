<?php

namespace Drupal\activity_creator\Service;

use Drupal\Core\Batch\BatchBuilder;

/**
 * Class ActivityCreatorBatchActivityDeletion.
 *
 * Remove activities in batch.
 *
 * @package Drupal\activity_creator
 */
class ActivityCreatorBatchActivityDeletion {

  /**
   * Delete activities in a batch.
   *
   * @param array $ids
   *   Activity ids to be deleted.
   */
  public static function bulkDeleteActivities(array $ids): void {
    // Define batch process to delete activities.
    $batch_builder = (new BatchBuilder())
      ->setTitle(t('Deleting activities...'))
      ->setFinishCallback([
        ActivityCreatorBatchActivityDeletion::class,
        'finishProcess',
      ])
      ->addOperation([ActivityCreatorBatchActivityDeletion::class, 'updateProcess'], [$ids]);

    batch_set($batch_builder->toArray());
  }

  /**
   * Process operation to delete activities retrieved from init operation.
   *
   * @param array $items
   *   Items.
   * @param array $context
   *   An array that may or may not contain placeholder variables.
   */
  public static function updateProcess(array $items, array &$context): void {
    /** @var \Drupal\activity_creator\ActivityNotifications $activity_notification_service */
    $activity_notification_service = \Drupal::service('activity_creator.activity_notifications');
    $activity_storage = \Drupal::entityTypeManager()->getStorage('activity');

    // Elements per operation.
    $limit = 50;

    // Set default progress values.
    if (empty($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($items);
    }

    // Save items to array which will be changed during processing.
    if (empty($context['sandbox']['items'])) {
      $context['sandbox']['items'] = $items;
    }

    if (!empty($context['sandbox']['items'])) {
      // Get items for processing.
      $current_ids = array_splice($context['sandbox']['items'], 0, $limit);

      // Load activities by activity IDs.
      $activities = $activity_storage->loadMultiple($current_ids);
      $activity_storage->delete($activities);

      // Remove entries from activity_notification_table.
      $activity_notification_service->deleteNotificationsbyIds($current_ids);

      $context['sandbox']['progress'] += count($current_ids);

      $context['message'] = t('Now processing activities :progress of :count', [
        ':progress' => $context['sandbox']['progress'],
        ':count' => $context['sandbox']['max'],
      ]);

      // Increment total processed item values. Will be used in finished
      // callback.
      $context['results']['processed'] = $context['sandbox']['progress'];

    }

    // If not finished all tasks, we count percentage of process. 1 = 100%.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Callback for finished batch events.
   *
   * @param bool $success
   *   TRUE if the update was fully succeeded.
   * @param array $results
   *   Contains individual results per operation.
   * @param array $operations
   *   Contains the unprocessed operations that failed or weren't touched yet.
   */
  public static function finishProcess($success, array $results, array $operations): void {
    $message = t('Number of activities deleted by batch: @count', [
      '@count' => $results['processed'],
    ]);

    \Drupal::logger('activity_creator')->info($message);
  }

}

<?php

namespace Drupal\activity_creator\Plugin\QueueWorker;

/**
 * A report worker.
 *
 * @QueueWorker(
 *   id = "activity_creator_logger",
 *   title = @Translation("Process activity loggers."),
 *   cron = {"time" = 60}
 * )
 *
 * This QueueWorker is responsible for processing ActivityLog items and will
 * retrieve recipients batched and then create new queue items for processing in
 * QueueWorker ActivityWorkerActivities.
 */
class ActivityWorkerLogger extends ActivityWorkerBase {

  /**
   * The ActivityContext manager.
   *
   * @var \Drupal\activity_creator\Plugin\ActivityContextManager
   */
  protected $contextPluginManager;

  /**
   * The state.
   *
   * @var \Drupal\Core\State\
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {

    // Get 100 Recipients at a time.
    $limit = 0;
    // @todo Move all this logic to a service.
    $context_plugin_manager = \Drupal::service('plugin.manager.activity_context.processor');

    /** @var \Drupal\activity_creator\Plugin\ActivityContextBase $plugin */
    // @todo Do we need multiple context plugins? If so should we call Manager?
    $plugin = $context_plugin_manager->createInstance($data['context']);
    $recipients = $plugin->getRecipients($data, $data['last_uid'], $limit);

    if (!empty($recipients)) {
      // Default activity creator template.
      $activity_creator_data = [
        'mid' => $data['mid'],
        'message_template' => $data['message_template'],
        'actor' => $data['actor'],
        'context' => $data['context'],
        'destination' => $data['destination'],
        'related_object' => $data['related_object'],
      ];

      // Get all the activity recipient types. Maintain target IDs as key.
      $activity_by_type = array_column($recipients, 'target_type');
      foreach ($activity_by_type as $recipients_key => $target_type) {
        // For all one to one target entity types we create an activity.
        if ($target_type !== 'user') {
          $activity_creator_data['recipient'] = $recipients[$recipients_key];
          $this->createQueueItem('activity_creator_activities', $activity_creator_data);
        }

        if ($target_type === 'user') {
          $user_recipients[] = $recipients[$recipients_key];
        }
        $last_uid = $recipients[$recipients_key];
      }

      // When the activity should be created for a one to many user entity
      // we like to group these.
      if (!empty($user_recipients)) {
        $activity_creator_data['recipient'] = $user_recipients;
        $this->createQueueItem('activity_creator_activities', $activity_creator_data);
      }

      // Now create new queue item for activity_creator_logger if necessary.
      if ($limit !== 0 && isset($last_uid) && count($recipients) >= $limit) {
        $data['last_uid'] = $last_uid;
        $data['status'] = 'processing';
        $this->createQueueItem('activity_creator_logger', $data);
      }
    }
    else {
      $activity_creator_data = [
        'mid' => $data['mid'],
        'message_template' => $data['message_template'],
        'actor' => $data['actor'],
        // Not necessary?
        'context' => $data['context'],
        'destination' => $data['destination'],
        'related_object' => $data['related_object'],
      ];
      $this->createQueueItem('activity_creator_activities', $activity_creator_data);
    }

  }

}

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
  protected $context_plugin_manager;

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
    // TODO: Move all this logic to a service.
    // TODO: Change this to use dependency injection.
    $context_plugin_manager = \Drupal::service('plugin.manager.activity_context.processor');

    /* @var $plugin \Drupal\activity_creator\Plugin\ActivityContextBase */
    // @TODO Do we need multiple context plugins? If so should we call Manager?
    $plugin = $context_plugin_manager->createInstance($data['context']);
    $recipients = $plugin->getRecipients($data, $data['last_uid'], $limit);

    if (!empty($recipients)) {

      foreach ($recipients as $recipient) {
        // Create a queue item for activity creation.
        $activity_creator_data = [
          'mid' => $data['mid'],
          'message_template' => $data['message_template'],
          'actor' => $data['actor'],
          'context' => $data['context'],
          'destination' => $data['destination'],
          'related_object' => $data['related_object'],
          'recipient' => $recipient,
        ];
        $this->createQueueItem('activity_creator_activities', $activity_creator_data);

        $last_uid = $recipient;
      }

      // Now create new queue item for activity_creator_logger if necessary.
      if ($limit != 0 && count($recipients) >= $limit && isset($last_uid)) {
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

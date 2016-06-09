<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\QueueWorker\ActivityWorkerLogger
 */

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
   * {@inheritdoc}
   */
  public function processItem($data) {

    $data['mid']; // message id, or maybe full ActivityLog obj? Better not!
//    $data['output_text']; // with tokens .. activityfactory will replace tokens
    $data['message_type']; // this is needed for the activityfactory
    $data['entity_type']; // not sure
    $data['entity_id']; // not sure
    $data['context']; // either group, profile, community
    $data['last_uid']; // last processed_uid
//    $data['status']; // Perhaps to store the status of this queue item: 1, 2, 3

    // @TODO Replace with the recipients service.
    // Get 100 Recipients at a time.
    $limit = 100;
    $recipients = array(1, 2, 3, 4, 5);

    if (!empty($recipients)) {

      foreach ($recipients as $recipient) {
        // Probably Recipient is an object.

        // Create new activity_creator_activities QueueItem..
        $last_uid = $recipient;  // @TODO Put uid in here or object?
      }

      // Now create new queue item for activity_creator_logger if necessary.
      // @TODO Discuss if $last_uid isset is justified here.
      if (count($recipients) >= $limit && isset($last_uid)) {
        $data['last_uid'] = $last_uid;
        $data['status'] = 'processing';
        $this->createQueueItem('activity_creator_logger', $data);
      }

    }

//    $this->reportWork(1, $data);
  }

}

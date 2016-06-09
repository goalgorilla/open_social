<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\QueueWorker\ActivityWorkerActivities
 */

namespace Drupal\activity_creator\Plugin\QueueWorker;

/**
 * A report worker.
 *
 * @QueueWorker(
 *   id = "activity_creator_activities",
 *   title = @Translation("Process activity activities."),
 *   cron = {"time" = 60}
 * )
 *
 * This QueueWorker is responsible for creating Activity entities and will
 * retrieve use information provided by activity_creator_logger.
 */
class ActivityWorkerActivities extends ActivityWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {

    $data->mid; // message id, or maybe full ActivityLog obj? Better not!
    $data->output_text; // with tokens .. activityfactory will replace tokens
    $data->message_type; // this is needed for the activityfactory
    $data->entity_type; // not sure
    $data->entity_id; // not sure
    $data->context; // either group, profile, community
    $data->last_uid; // last processed_uid
    $data->status; // Perhaps to store the status of this queue item: 1, 2, 3

    // Neccessary for activity factory:
    // - verb (or string with tokens)
    // - message_text
    // - type (;e.g. create_topic_node)
    // - entity: entity_id & type
    // - actor
    // - context
    // - destinations (allowed? - messageType/ActivityLog)
    // - activityLog ID (message ID)

    // @TODO We should implement the ActivityFactory here.
    // What does this factory need to create new activity items?

//    $this->reportWork(1, $data);
  }

}

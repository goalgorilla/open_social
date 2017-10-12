<?php

namespace Drupal\activity_send_email\Plugin\QueueWorker;

use Drupal\activity_send_email\Plugin\ActivityDestination\EmailActivityDestination;
use Drupal\activity_send\Plugin\QueueWorker\ActivitySendWorkerBase;
use Drupal\activity_creator\Entity\Activity;
use Drupal\message\Entity\Message;
use Drupal\user\Entity\User;

/**
 * An activity send email worker.
 *
 * @QueueWorker(
 *   id = "activity_digest_worker",
 *   title = @Translation("Process activity_digest_worker queue."),
 *   cron = {"time" = 60}
 * )
 *
 * This QueueWorker is responsible for sending emails from the queue
 */
class ActivityDigestWorker extends ActivitySendWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // First make sure it's an actual Activity entity.
    if (!empty($data['entity_id']) && $activity = Activity::load($data['entity_id'])) {
      // Get target account.

    }
  }

}

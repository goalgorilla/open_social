<?php

namespace Drupal\social_advanced_queue\Plugin\ActivitySend;

use Drupal\activity_send_email\Plugin\ActivitySend\EmailActivitySend;
use Drupal\advancedqueue\Entity\Queue;
use Drupal\advancedqueue\Job;

/**
 * Ensures the'EmailActivitySend' activity action uses Advanced Queue Jobs.
 */
class EmailActivitySendAdvancedQueue extends EmailActivitySend {

  /**
   * {@inheritdoc}
   */
  public function create($entity) {
    $data = [];
    $data['entity_id'] = $entity->id();
    // Create a new Email Job and add to the "default" queue using
    // advanced queue API instead of SocialSendEmails default Core Queue.
    $job = Job::create('activity_send_email_worker', $data);
    if ($job instanceof Job) {
      $queue = Queue::load('default');
      $queue->enqueueJob($job);
    }
  }

}

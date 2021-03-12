<?php

namespace Drupal\social_advanced_queue\Plugin\Action;

use Drupal\social_user\Plugin\Action\SocialSendEmail;
use Drupal\advancedqueue\Entity\Queue;
use Drupal\advancedqueue\Job;

/**
 * An example action covering most of the possible options.
 */
class SocialSendEmailAdvancedQueue extends SocialSendEmail {

  /**
   * Create Queue Item.
   *
   * @param string $name
   *   The name of the queue.
   * @param array $data
   *   The queue data.
   */
  public function createQueueItem($name, array $data) {
    // Create a new Email Job and add to the "default" queue using
    // advanced queue API instead of SocialSendEmails default Core Queue.
    $job = Job::create($name, $data);
    if ($job instanceof Job) {
      $queue = Queue::load('default');
      $queue->enqueueJob($job);
    }

  }

}

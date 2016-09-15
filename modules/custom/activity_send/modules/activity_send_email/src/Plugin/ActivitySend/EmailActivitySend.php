<?php

/**
 * @file
 * Contains \Drupal\activity_send_email\Plugin\ActivitySend\EmailActivitySend.
 */

namespace Drupal\activity_send_email\Plugin\ActivitySend;

use Drupal\activity_send\Plugin\ActivitySendBase;

/**
 * Provides a 'EmailActivitySend' activity action.
 *
 * @ActivitySend(
 *  id = "email_activity_send",
 *  label = @Translation("Action that is triggered when a entity is created"),
 * )
 */
class EmailActivitySend extends ActivitySendBase {

  /**
   * @inheritdoc
   */
  public function create($entity) {
    $data['entity_id'] = $entity->id();
    $queue = \Drupal::queue('activity_send_email_worker');
    $queue->createItem($data);
  }

}

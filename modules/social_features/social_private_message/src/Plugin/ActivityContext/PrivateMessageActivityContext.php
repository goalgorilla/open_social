<?php

namespace Drupal\social_private_message\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\private_message\Entity\PrivateMessageThreadInterface;

/**
 * Provides a 'PrivateMessageActivityContext' activity context.
 *
 * @ActivityContext(
 *  id = "private_message_activity_context",
 *  label = @Translation("Private message activity context"),
 * )
 */
class PrivateMessageActivityContext extends ActivityContextBase {

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    $recipients = [];

    // We only know the context if there is a related object.
    if (isset($data['related_object']) && !empty($data['related_object'])) {
      $related_object = $data['related_object'][0];
      if ($related_object['target_type'] == 'private_message') {

        $related_object = $data['related_object'][0];
        $pm_storage = \Drupal::entityTypeManager()->getStorage('private_message');

        if ($related_object['target_type'] == 'private_message') {
          $private_message = $pm_storage->load($related_object['target_id']);
          $pmService = \Drupal::service('private_message.service');
          $thread = $pmService->getThreadFromMessage($private_message);
          /** @var PrivateMessageThreadInterface $members */
          $members = $thread->getMembers();
        }
//        $privateMessageId = $data['related_object'][0]['target_id'];
//
//        $pmService = \Drupal::service('private_message.service');
//        $thread = $pmService->getThreadFromMessage(PrivateMessage::load(4));
//
//        /** @var PrivateMessageThreadInterface $members */
//        $members = $thread->getMembers();

      }
    }
    return $recipients;
  }

  /**
   * Check if it's valid.
   */
  public function isValidEntity($entity) {
    if ($entity->getEntityTypeId() === 'private_message') {
      return TRUE;
    }

    return FALSE;
  }

}

<?php

namespace Drupal\social_private_message\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\private_message\Entity\PrivateMessage;
use Drupal\user\Entity\User;

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

        if ($related_object['target_type'] === 'private_message') {
          $private_message = PrivateMessage::load($related_object['target_id']);
          // Must be a Private Message.
          if ($private_message instanceof PrivateMessage) {
            $pmService = \Drupal::service('private_message.service');
            // Get the thread of this message.
            $thread = $pmService->getThreadFromMessage($private_message);
            // Get all members of this thread.
            /** @var \Drupal\private_message\Entity\PrivateMessageThreadInterface $members */
            $members = $thread->getMembers();
            // Loop over all PMT participants.
            foreach ($members as $member) {
              if ($member instanceof User) {
                // Filter out the author of this message.
                if ($member->id() == $data['actor']) {
                  continue;
                }
                // Create the recipients array.
                $recipients[] = [
                  'target_type' => 'user',
                  'target_id' => $member->id(),
                ];
              }
            }
          }
        }
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

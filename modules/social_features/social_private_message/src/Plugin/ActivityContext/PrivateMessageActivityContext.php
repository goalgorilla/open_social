<?php

namespace Drupal\social_private_message\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\private_message\Entity\PrivateMessage;
use Drupal\private_message\Entity\PrivateMessageThread;
use Drupal\private_message\Entity\PrivateMessageThreadInterface;
use Drupal\private_message\Service\PrivateMessageService;

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
        $privateMessageId = $data['related_object'][0]['target_id'];

        $pmService = \Drupal::service('private_message.service');
        $thread = $pmService->getThreadFromMessage(PrivateMessage::load(32));

        /** @var PrivateMessageThreadInterface $members */
        $members = $thread->getMembers();

//        dpm($thread);
//        $vote = $vote_storage->load($related_object['target_id']);
//        if ($vote instanceof Vote) {
//          $entity_storage = \Drupal::entityTypeManager()->getStorage($vote->getVotedEntityType());
//          /** @var \Drupal\Core\Entity\Entity $entity */
//          $entity = $entity_storage->load($vote->getVotedEntityId());
//
//          $recipients[] = [
//            'target_type' => 'user',
//            'target_id' => $entity->getOwnerId(),
//          ];
//        }
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

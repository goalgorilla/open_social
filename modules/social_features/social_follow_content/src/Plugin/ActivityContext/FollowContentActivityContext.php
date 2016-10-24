<?php

/**
 * @file
 * Contains \Drupal\social_follow_content\Plugin\ActivityContext\FollowContentActivityContext.
 */

namespace Drupal\social_follow_content\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\activity_creator\ActivityFactory;


/**
 * Provides a 'FollowContentActivityContext' activity context plugin.
 *
 * @ActivityContext(
 *  id = "follow_content_activity_context",
 *  label = @Translation("Following content activity context"),
 * )
 */
class FollowContentActivityContext extends ActivityContextBase {

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    $recipients = [];

    // We only know the context if there is a related object.
    if (isset($data['related_object']) && !empty($data['related_object'])) {
      $related_entity = ActivityFactory::getActivityRelatedEntity($data);

      if ($related_entity['target_type'] == 'node') {
        $recipients += $this->getRecipientsWhoFollowContent($related_entity, $data);
      }
    }

    return $recipients;
  }

  /**
   * Returns owner recipient from entity.
   */
  public function getRecipientsWhoFollowContent(array $related_entity, array $data) {
    $recipients = [];

    $storage = \Drupal::entityTypeManager()->getStorage('flagging');
    $flaggings = $storage->loadByProperties([
      'flag_id' => 'follow_content',
      'entity_type' => $related_entity['target_type'],
      'entity_id' => $related_entity['target_id'],
    ]);

    // We don't send notifications to users about their own comments.
    $original_related_object = $data['related_object'][0];
    if (isset($original_related_object['target_type']) && $original_related_object['target_type'] == 'comment') {
      $storage = \Drupal::entityTypeManager()
        ->getStorage($original_related_object['target_type']);
      $original_related_entity = $storage->load($original_related_object['target_id']);
    }

    foreach ($flaggings as $flagging) {
      $recipient_id = $flagging->getOwner()->id();
      if ($recipient_id != $original_related_entity->getOwnerId()) {
        $recipients[] = [
          'target_type' => 'user',
          'target_id' => $flagging->getOwner()->id(),
        ];
      }
    }

    return $recipients;
  }

}

<?php

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

      if (in_array($related_entity['target_type'], ['node', 'post'])) {
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

    $storage = $this->entityTypeManager->getStorage('flagging');

    $flaggings = $storage->loadByProperties([
      'flag_id' => ['follow_content', 'follow_post'],
      'entity_type' => $related_entity['target_type'],
      'entity_id' => $related_entity['target_id'],
    ]);

    // We don't send notifications to users about their own comments.
    $original_related_object = $data['related_object'][0];
    $original_related_entity = $this->entityTypeManager
      ->getStorage($original_related_object['target_type'])
      ->load($original_related_object['target_id']);

    foreach ($flaggings as $flagging) {
      $recipient = $flagging->getOwner();
      if ($recipient->id() != $original_related_entity->getOwnerId()) {
        if ($original_related_entity->access('view', $recipient)) {
          $recipients[] = [
            'target_type' => 'user',
            'target_id' => $recipient->id(),
          ];
        }
      }
    }

    return $recipients;
  }

}

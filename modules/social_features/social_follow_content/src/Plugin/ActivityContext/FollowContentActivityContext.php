<?php

namespace Drupal\social_follow_content\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\social_comment\Entity\Comment;
use Drupal\social_node\Entity\Node;
use Drupal\user\UserInterface;

/**
 * Provides a 'FollowContentActivityContext' activity context plugin.
 *
 * @ActivityContext(
 *   id = "follow_content_activity_context",
 *   label = @Translation("Following content activity context"),
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
      $related_entity = $this->activityFactory->getActivityRelatedEntity($data);

      if ($related_entity['target_type'] == 'node') {
        $recipients += $this->getRecipientsWhoFollowContent($related_entity, $data);
      }
    }

    return $recipients;
  }

  /**
   * Returns owner recipient from entity.
   *
   * @param array $related_entity
   *   The related entity.
   * @param array $data
   *   The data.
   *
   * @return array
   *   An associative array of recipients, containing the following key-value
   *   pairs:
   *   - target_type: The entity type ID.
   *   - target_id: The entity ID.
   */
  public function getRecipientsWhoFollowContent(array $related_entity, array $data) {
    $recipients = [];

    $storage = $this->entityTypeManager->getStorage('flagging');
    $flaggings = $storage->loadByProperties([
      'flag_id' => 'follow_content',
      'entity_type' => $related_entity['target_type'],
      'entity_id' => $related_entity['target_id'],
    ]);

    // We don't send notifications to users about their own comments.
    $original_related_object = $data['related_object'][0];
    $storage = $this->entityTypeManager->getStorage($original_related_object['target_type']);
    $original_related_entity = $storage->load($original_related_object['target_id']);

    foreach ($flaggings as $flagging) {
      /** @var \Drupal\flag\FlaggingInterface $flagging */
      $recipient = $flagging->getOwner();

      // It could happen that a notification has been queued but the content or
      // account has since been deleted. In that case we can find no recipient.
      if (!$recipient instanceof UserInterface) {
        continue;
      }

      // The owner of a node automatically follows his / her own content.
      // Because of this, we do not want to send a follow notification.
      if ($original_related_entity instanceof Comment) {
        // We need to compare the owner ID of the original node to the one
        // being the current recipient.
        $original_node = $original_related_entity->getCommentedEntity();
        if ($original_node instanceof Node && $recipient->id() === $original_node->getOwnerId()) {
          continue;
        }
      }

      if ($recipient->id() !== $original_related_entity->getOwnerId() && $original_related_entity->access('view', $recipient)) {
        $recipients[] = [
          'target_type' => 'user',
          'target_id' => $recipient->id(),
        ];
      }
    }
    return $recipients;
  }

}

<?php

namespace Drupal\social_mentions\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\user\Entity\User;

/**
 * Provides a 'MentionActivityContext' activity context.
 *
 * @ActivityContext(
 *  id = "mention_activity_context",
 *  label = @Translation("Mention activity context"),
 * )
 */
class MentionActivityContext extends ActivityContextBase {

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    $recipients = [];
    $mentions = [];

    // We only know the context if there is a related object.
    if (isset($data['related_object']) && !empty($data['related_object'])) {
      $related_object = $data['related_object'][0];
      $mentions_storage = \Drupal::entityTypeManager()->getStorage('mentions');

      if ($related_object['target_type'] == 'mentions') {
        $mentions[] = $mentions_storage->load($related_object['target_id']);
      }
      else {
        $entity_storage = \Drupal::entityTypeManager()
          ->getStorage($related_object['target_type']);
        $entity = $entity_storage->load($related_object['target_id']);
        $mentions = $this->getMentionsFromRelatedEntity($entity);
      }

      if (!empty($mentions)) {
        foreach ($mentions as $mention) {
          if (isset($mention->uid)) {
            $uid = $mention->getMentionedUserId();

            // Don't send notifications to myself.
            if ($uid === $data['actor']) {
              continue;
            }

            $entity_storage = \Drupal::entityTypeManager()
              ->getStorage($mention->getMentionedEntityTypeId());
            $mentioned_entity = $entity_storage->load($mention->getMentionedEntityId());

            $account = User::load($uid);
            $access = $mentioned_entity->access('view', $account);

            if ($access) {
              $recipients[] = [
                'target_type' => 'user',
                'target_id' => $mention->getMentionedUserId(),
              ];
            }
          }
        }
      }

    }

    return $recipients;
  }

  /**
   * Check for valid entity.
   */
  public function isValidEntity($entity) {
    if ($entity->getEntityTypeId() === 'mentions') {
      return TRUE;
    }

    // Special cases for comments and posts.
    $allowed_content_types = [
      'comment',
    ];
    if (in_array($entity->getEntityTypeId(), $allowed_content_types)) {
      $mentions = $this->getMentionsFromRelatedEntity($entity);
      if (!empty($mentions)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Get the mentions from the related entity.
   */
  public function getMentionsFromRelatedEntity($entity) {
    if ($entity->getEntityTypeId() === 'comment') {
      if ($entity->getParentComment()) {
        $entity = $entity->getParentComment();
      }
    }
    // Mention entity can't be loaded at time of new post or comment creation.
    $mentions = \Drupal::entityTypeManager()
      ->getStorage('mentions')
      ->loadByProperties([
        'entity_type' => $entity->getEntityTypeId(),
        'entity_id' => $entity->id(),
      ]);
    return $mentions;
  }

}

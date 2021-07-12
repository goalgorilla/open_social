<?php

namespace Drupal\activity_basics\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\group\Entity\GroupContent;

/**
 * Provides a 'ProfileActivityContext' activity context.
 *
 * @ActivityContext(
 *   id = "profile_activity_context",
 *   label = @Translation("Profile activity context"),
 * )
 */
class ProfileActivityContext extends ActivityContextBase {

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    $recipients = [];

    // We only know the context if there is a related object.
    if (isset($data['related_object']) && !empty($data['related_object'])) {
      $referenced_entity = $this->activityFactory->getActivityRelatedEntity($data);

      if ($referenced_entity['target_type'] === 'post') {
        $recipients += $this->getRecipientsFromPost($referenced_entity);
      }
    }

    return $recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidEntity(EntityInterface $entity) {
    // Special cases for comments.
    if ($entity->getEntityTypeId() === 'comment') {
      $comment_owner_id = $entity->getOwnerId();

      // Returns the entity to which the comment is attached.
      $entity = $entity->getCommentedEntity();
    }

    if (!isset($entity)) {
      return FALSE;
    }

    // Check if it's placed in a group (regardless off content type).
    if (GroupContent::loadByEntity($entity)) {
      return FALSE;
    }

    if ($entity->getEntityTypeId() === 'post') {
      if (!$entity->field_recipient_group->isEmpty()) {
        return FALSE;
      }
      elseif (!$entity->field_recipient_user->isEmpty()) {
        if (isset($comment_owner_id)) {
          return $comment_owner_id !== $entity->field_recipient_user->target_id;
        }

        return TRUE;
      }
    }

    return FALSE;
  }

}

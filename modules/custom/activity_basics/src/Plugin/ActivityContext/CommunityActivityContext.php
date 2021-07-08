<?php

namespace Drupal\activity_basics\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\group\Entity\GroupContent;

/**
 * Provides a 'CommunityActivityContext' activity context.
 *
 * @ActivityContext(
 *   id = "community_activity_context",
 *   label = @Translation("Community activity context"),
 * )
 */
class CommunityActivityContext extends ActivityContextBase {

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    // Always return empty array here. Since community does not have specific
    // recipients.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isValidEntity(EntityInterface $entity) {
    // Special cases for comments.
    if ($entity->getEntityTypeId() === 'comment') {
      // Returns the entity to which the comment is attached.
      $entity = $entity->getCommentedEntity();
    }

    if (!isset($entity)) {
      return FALSE;
    }

    // Check if the content is placed in a group (regardless of content type).
    if (GroupContent::loadByEntity($entity)) {
      return FALSE;
    }

    if ($entity->getEntityTypeId() === 'post') {
      if (!$entity->field_recipient_group->isEmpty()) {
        return FALSE;
      }
      elseif (!$entity->field_recipient_user->isEmpty()) {
        return FALSE;
      }
    }

    return TRUE;
  }

}

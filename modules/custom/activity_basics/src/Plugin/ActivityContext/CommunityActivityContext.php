<?php

namespace Drupal\activity_basics\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\group\Entity\GroupContent;

/**
 * Provides a 'CommunityActivityContext' acitivy context.
 *
 * @ActivityContext(
 *  id = "community_activity_context",
 *  label = @Translation("Community activity context"),
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
  public function isValidEntity($entity) {
    // Special cases for comments.
    if ($entity->getEntityTypeId() === 'comment') {
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
      if (!empty($entity->get('field_recipient_group')->getValue())) {
        return FALSE;
      }
      elseif (!empty($entity->get('field_recipient_user')->getValue())) {
        return FALSE;
      }
    }
    return TRUE;
  }

}

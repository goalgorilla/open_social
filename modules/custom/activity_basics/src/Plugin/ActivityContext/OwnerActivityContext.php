<?php

/**
 * @file
 * Contains \Drupal\activity_basics\Plugin\ActivityContext\OwnerActivityContext.
 */

namespace Drupal\activity_basics\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\activity_creator\ActivityFactory;


/**
 * Provides a 'OwnerActivityContext' activity context.
 *
 * @ActivityContext(
 *  id = "owner_activity_context",
 *  label = @Translation("Owner activity context"),
 * )
 */
class OwnerActivityContext extends ActivityContextBase {

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    $recipients = [];

    // We only know the context if there is a related object.
    if (isset($data['related_object']) && !empty($data['related_object'])) {

      $referenced_entity = ActivityFactory::getActivityRelatedEntity($data);

      if ($referenced_entity['target_type'] === 'post') {
        $recipients += $this->getRecipientOwnerFromPost($referenced_entity);
      }

      if ($referenced_entity['target_type'] === 'node') {
        $recipients += $this->getRecipientOwnerFromNode($referenced_entity);
      }

    }

    return $recipients;
  }

  public function isValidEntity($entity) {
    // Returns the entity to which the comment is attached.
    if ($entity->getEntityTypeId() === 'comment') {
      $entity = $entity->getCommentedEntity();
    }

    if ($entity->getEntityTypeId() === 'post') {
      if (!empty($entity->get('field_recipient_group')->getValue())) {
        return FALSE;
      }
      elseif (!empty($entity->get('field_recipient_user')->getValue())) {
        return TRUE;
      }
    }
    return TRUE;
  }

}

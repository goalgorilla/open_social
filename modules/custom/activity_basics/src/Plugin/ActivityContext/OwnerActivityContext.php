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
      $allowed_entity_types = ['node', 'post'];
      if (in_array($referenced_entity['target_type'], $allowed_entity_types)) {
        $recipients += $this->getRecipientOwnerFromEntity($referenced_entity);
      }
    }

    return $recipients;
  }

  public function isValidEntity($entity) {
    // @TODO: Add some check for owner.
    return TRUE;
  }

}

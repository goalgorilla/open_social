<?php

namespace Drupal\activity_basics\Plugin\ActivityContext;

use Drupal\activity_creator\ActivityFactory;
use Drupal\activity_creator\Plugin\ActivityContextBase;

/**
 * Provides a 'OwnerActivityContext' activity context.
 *
 * @ActivityContext(
 *  id = "platform_activity_context",
 *  label = @Translation("Platform activity context"),
 * )
 */
class PlatformActivityContext extends ActivityContextBase {

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    $recipients = [];

    // We only know the context if there is a related object.
    if (isset($data['related_object']) && !empty($data['related_object'])) {
      $related_entity = $this->activityFactory->getActivityRelatedEntity($data);
      // Load the related entity.
      $entity_storage = \Drupal::entityTypeManager()
        ->getStorage($related_entity['target_type']);
      $entity = $entity_storage->load($related_entity['target_id']);

      // When nothing found return the empty recipients array. Basically means
      // there is no activity sent.
      if ($entity === NULL) {
        return $recipients;
      }

      // Add the owner of the related entity as a recipient.
      // No owner found set user 1.
      $recipients[] = [
        'target_type' => 'user',
        'target_id' => $entity->getOwnerId() ?? 1,
      ];
    }

    return $recipients;
  }

}

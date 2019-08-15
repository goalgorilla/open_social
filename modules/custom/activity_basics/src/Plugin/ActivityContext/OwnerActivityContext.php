<?php

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
      $related_entity = ActivityFactory::getActivityRelatedEntity($data);
      $allowed_entity_types = ['node', 'post', 'comment'];
      if (in_array($related_entity['target_type'], $allowed_entity_types)) {
        $recipients += $this->getRecipientOwnerFromEntity($related_entity, $data);
      }
    }

    // Remove the actor (user performing action) from recipients list.
    if (!empty($data['actor'])) {
      $key = array_search($data['actor'], array_column($recipients, 'target_id'), FALSE);
      if ($key !== FALSE) {
        unset($recipients[$key]);
      }
    }

    return $recipients;
  }

  /**
   * Returns owner recipient from entity.
   */
  public function getRecipientOwnerFromEntity(array $related_entity, array $data) {
    $recipients = [];

    $entity_storage = \Drupal::entityTypeManager()
      ->getStorage($related_entity['target_type']);
    $entity = $entity_storage->load($related_entity['target_id']);

    // It could happen that a notification has been queued but the content
    // has since been deleted. In that case we can find no additional
    // recipients.
    if (!$entity) {
      return $recipients;
    }

    // Don't return recipients if user comments on own content.
    $original_related_object = $data['related_object'][0];
    if (isset($original_related_object['target_type']) && $original_related_object['target_type'] == 'comment') {
      $storage = \Drupal::entityTypeManager()
        ->getStorage($original_related_object['target_type']);
      $original_related_entity = $storage->load($original_related_object['target_id']);

      if (!empty($original_related_entity) && $original_related_entity->getOwnerId() == $entity->getOwnerId()) {
        return $recipients;
      }
    }

    if (isset($original_related_object['target_type']) && $original_related_object['target_type'] == 'event_enrollment') {
      $storage = \Drupal::entityTypeManager()
        ->getStorage($original_related_object['target_type']);
      $original_related_entity = $storage->load($original_related_object['target_id']);

      if (!empty($original_related_entity) && $original_related_entity->getAccount() !== NULL) {
        $recipients[] = [
          'target_type' => 'user',
          'target_id' => $original_related_entity->getAccount(),
        ];

        return $recipients;
      }
    }

    $recipients[] = [
      'target_type' => 'user',
      'target_id' => $entity->getOwnerId(),
    ];

    return $recipients;
  }

}

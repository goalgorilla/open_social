<?php

namespace Drupal\social_event\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\social_event\EventEnrollmentInterface;

/**
 * Provides a 'OwnerEventEnrollmentActivityContext' activity context.
 *
 * @ActivityContext(
 *   id = "owner_event_enrollment_activity_context",
 *   label = @Translation("Owner event enrollment context"),
 * )
 */
class OwnerEventEnrollmentActivityContext extends ActivityContextBase {

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, int $last_id, int $limit): array {
    if (empty($data['related_object'][0])) {
      return [];
    }

    $related_object = $data['related_object'][0];
    $storage = $this->entityTypeManager->getStorage($related_object['target_type']);
    $enrollment = $storage->load($related_object['target_id']);

    if (!($enrollment instanceof EventEnrollmentInterface)) {
      return [];
    }

    $recipients = [];

    // Event enrollment was created by user itself.
    if ($enrollment->getOwnerId() === $enrollment->getAccount()) {
      $recipients[] = [
        'target_type' => 'user',
        'target_id' => $enrollment->getAccount(),
      ];
    }

    return $recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidEntity(EntityInterface $entity): bool {
    return $entity->getEntityTypeId() === 'event_enrollment';
  }

}

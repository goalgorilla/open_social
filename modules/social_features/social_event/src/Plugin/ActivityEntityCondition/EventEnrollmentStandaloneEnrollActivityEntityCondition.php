<?php

namespace Drupal\social_event\Plugin\ActivityEntityCondition;

use Drupal\activity_creator\Plugin\ActivityEntityConditionBase;
use Drupal\node\NodeInterface;

/**
 * Checks if event of even_enrollments allows to send specific mails to users.
 *
 * @ActivityEntityCondition(
 *  id = "event_enrollment_standalone_enroll",
 *  label = @Translation("Event Enrollment - Send confirmation after standalone enroll"),
 *  entities = {"event_enrollment" = {}}
 * )
 */
class EventEnrollmentStandaloneEnrollActivityEntityCondition extends ActivityEntityConditionBase {

  /**
   * {@inheritdoc}
   */
  public function isValidEntityCondition($entity): bool {
    if ($entity->getEntityTypeId() === 'event_enrollment') {
      /** @var \Drupal\social_event\EventEnrollmentInterface $entity */
      $event = $entity->getEvent();

      if (
        $event instanceof NodeInterface &&
        $event->hasField('field_event_send_confirmation') &&
        (bool) $event->get('field_event_send_confirmation')->getString()
      ) {
        return TRUE;
      }
    }

    return FALSE;
  }

}

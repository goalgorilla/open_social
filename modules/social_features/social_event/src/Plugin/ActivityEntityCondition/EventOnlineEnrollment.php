<?php

namespace Drupal\social_event\Plugin\ActivityEntityCondition;

use Drupal\activity_creator\Plugin\ActivityEntityConditionBase;
use Drupal\social_event\Entity\Node\EventInterface;
use Drupal\social_event\EventEnrollmentInterface;

/**
 * Applied only to "event_enrollments" with online events.
 *
 * @ActivityEntityCondition(
 *  id = "event_online_enrollment_condition",
 *  label = @Translation("Event Online Enrollment"),
 *  entities = {"event_enrollment" = {}}
 * )
 */
class EventOnlineEnrollment extends ActivityEntityConditionBase {

  /**
   * {@inheritdoc}
   */
  public function isValidEntityCondition($entity): bool {
    if ($entity->getEntityTypeId() === 'event_enrollment') {
      assert($entity instanceof EventEnrollmentInterface);
      $event = $entity->getEvent();

      // This condition should be applied only for online events.
      if (!$event instanceof EventInterface || !$event->isOnline()) {
        return FALSE;
      }

      if (
        $event->hasField('field_event_send_confirmation') &&
        $event->get('field_event_send_confirmation')->getString()
      ) {
        return TRUE;
      }
    }

    return FALSE;
  }

}

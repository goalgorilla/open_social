<?php

namespace Drupal\social_event_an_enroll;

use Drupal\social_event\EventEnrollmentInterface;

/**
 * Class EventAnEnrollManager.
 */
class EventAnEnrollManager {

  /**
   * Returns guest name.
   *
   * @param \Drupal\social_event\EventEnrollmentInterface $entity
   *   The event enrollment.
   *
   * @return string
   *   Full name or E-mail address.
   */
  public function getGuestName(EventEnrollmentInterface $entity) {
    $parts = [];

    if (!$entity->field_first_name->isEmpty()) {
      $parts[] = $entity->field_first_name->value;
    }

    if (!$entity->field_last_name->isEmpty()) {
      $parts[] = $entity->field_last_name->value;
    }

    if (!$parts) {
      $parts[] = $entity->field_email->value;
    }

    return implode(' ', $parts);
  }

}

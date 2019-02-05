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
   * @param bool $email
   *   TRUE if can show E-mail address when first and last names is not set.
   *
   * @return string
   *   Full name or E-mail address.
   */
  public function getGuestName(EventEnrollmentInterface $entity, $email = TRUE) {
    $parts = [];

    if (!$entity->field_first_name->isEmpty()) {
      $parts[] = $entity->field_first_name->value;
    }

    if (!$entity->field_last_name->isEmpty()) {
      $parts[] = $entity->field_last_name->value;
    }

    if (!$parts && $email) {
      $parts[] = $entity->field_email->value;
    }

    return implode(' ', $parts);
  }

  /**
   * Check if enrollment user is guest.
   *
   * @param \Drupal\social_event\EventEnrollmentInterface $entity
   *   The event enrollment.
   *
   * @return bool
   *   TRUE if it is guest.
   */
  public function isGuest(EventEnrollmentInterface $entity) {
    return !$entity->field_account->target_id;
  }

}

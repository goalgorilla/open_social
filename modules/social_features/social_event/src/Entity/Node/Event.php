<?php

namespace Drupal\social_event\Entity\Node;

use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_node\Entity\Node as Node;

/**
 * Defines bundle class for event node type.
 */
class Event extends Node implements EventInterface {

  /**
   * {@inheritdoc}
   */
  public function isEnrollmentEnabled(): bool {
    // Get global event settings.
    $settings = \Drupal::config('social_event.settings');
    if (
      $settings->get('disable_event_enroll') ||
      !$this->hasField('field_event_enroll') ||
      (!$this->get('field_event_enroll')->isEmpty() && !$this->get('field_event_enroll')->getString())
    ) {
      return FALSE;
    }

    // When field was added to the event the value become `false` which
    // creates some inconsistent.
    $was_not_changed = $this->get('field_event_enroll')->isEmpty();
    $is_enabled = (bool) $this->get('field_event_enroll')->getString();

    // Make an exception for the invite enroll method.
    // This doesn't allow people to enroll themselves, but get invited.
    if (
      !$this->get('field_enroll_method')->isEmpty() &&
      (int) $this->get('field_enroll_method')->getString() === EventEnrollmentInterface::ENROLL_METHOD_INVITE
    ) {
      $is_enabled = TRUE;
    }

    return $was_not_changed || $is_enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function showEnrollments(): bool {
    // Enrollments can be show only in case when enrolls are enabled and
    // show status as well.
    return $this->isEnrollmentEnabled() &&
      !$this->get('field_hide_enrollments')->getString();
  }

}

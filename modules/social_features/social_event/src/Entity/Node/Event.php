<?php

namespace Drupal\social_event\Entity\Node;

use Drupal\social_node\Entity\Node as Node;

/**
 * Defines bundle class for event node type.
 */
class Event extends Node implements EventInterface {

  /**
   * {@inheritdoc}
   */
  public function showEnrollments(): bool {
    // Enrollments can be show only in case when enrolls are enabled and
    // show status as well.
    return \Drupal::service('social_event.enroll')->isEnabled($this) &&
      !$this->get('field_hide_enrollments')->getString();
  }

}

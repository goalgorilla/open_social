<?php

namespace Drupal\social_event\Entity\Node;

/**
 * Provides an interface for "Event" node bundle class.
 *
 * @ingroup social_event
 */
interface EventInterface {

  /**
   * Check if access to enrollments is open.
   *
   * @return bool
   *   The access status.
   */
  public function showEnrollments(): bool;

}

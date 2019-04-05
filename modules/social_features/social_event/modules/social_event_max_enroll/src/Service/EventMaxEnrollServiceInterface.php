<?php

namespace Drupal\social_event_max_enroll\Service;

use Drupal\node\NodeInterface;

/**
 * Interface EventMaxEnrollServiceInterface.
 *
 * @package Drupal\social_event_max_enroll\Service
 */
interface EventMaxEnrollServiceInterface {

  /**
   * Get count of enrollments per event.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check for.
   *
   * @return int
   *   The enrollments count.
   */
  public function getEnrollmentsNumber(NodeInterface $node): int;

  /**
   * Get number of enrollments still possible per event.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check for.
   *
   * @return int
   *   How many spots are left.
   */
  public function getEnrollmentsLeft(NodeInterface $node): int;

  /**
   * Check if anonymous enrollment is allowed for given event.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check for.
   *
   * @return bool
   *   Returns TRUE if feature is enabled, the node is an event and max enroll
   *   is configured.
   */
  public function isEnabled(NodeInterface $node): bool;

}

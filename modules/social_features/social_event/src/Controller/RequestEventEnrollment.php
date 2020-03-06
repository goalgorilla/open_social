<?php

namespace Drupal\social_event\Controller;

use Drupal\node\NodeInterface;

/**
 * Class RequestEventEnrollment.
 */
class RequestEventEnrollment {

  /**
   * Provides the form for requesting a group membership.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event node.
   */
  public function requestEnrollment(NodeInterface $node) {
  }

  /**
   * Provides the form for approving a requested group membership.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event node.
   */
  public function approveEnrollmentRequest(NodeInterface $node) {
  }

}

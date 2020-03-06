<?php

namespace Drupal\social_event_request_enroll\Controller;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;

/**
 * Class RequestEventEnrollment.
 */
class RequestEventEnrollment {

  use StringTranslationTrait;

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

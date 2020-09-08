<?php

namespace Drupal\social_event\Service;

use Drupal\node\NodeInterface;
use Drupal\social_event\EventEnrollmentInterface;

/**
 * Class SocialEventEnrollService.
 *
 * @package Drupal\social_event\Service
 */
class SocialEventEnrollService implements SocialEventEnrollServiceInterface {

  /**
   * {@inheritdoc}
   */
  public function isEnabled(NodeInterface $node) {
    if ($node->bundle() === 'event' && $node->hasField('field_event_enroll')) {
      $was_not_changed = $node->field_event_enroll->isEmpty();
      $is_enabled = $node->field_event_enroll->value;

      // Make an exception for the invite enroll method.
      // This doesn't allow people to enroll themselves, but get invited.
      if ((int) $node->get('field_enroll_method')->value === EventEnrollmentInterface::ENROLL_METHOD_INVITE) {
        $is_enabled = TRUE;
      }

      return $was_not_changed || $is_enabled;
    }

    return FALSE;
  }

}

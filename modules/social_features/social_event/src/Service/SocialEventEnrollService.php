<?php

namespace Drupal\social_event\Service;

use Drupal\node\NodeInterface;

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

      return $was_not_changed || $is_enabled;
    }

    return FALSE;
  }

}

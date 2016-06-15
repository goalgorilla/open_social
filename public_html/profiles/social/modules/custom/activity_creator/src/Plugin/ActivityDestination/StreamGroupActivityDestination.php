<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\ActivityDestination\StreamGroupActivityDestination.
 */

namespace Drupal\activity_creator\Plugin\ActivityDestination;
use Drupal\activity_creator\Plugin\ActivityDestinationBase;

/**
 * Provides a 'StreamGroupActivityDestination' acitivy destination.
 *
 * @ActivityDestination(
 *  id = "stream_group",
 *  label = @Translation("Stream (group)"),
 * )
 */
class StreamGroupActivityDestination extends ActivityDestinationBase {

  /**
   * {@inheritdoc}
   */
  public function getViewMode($original_view_mode, $entity) {
    $view_mode = $original_view_mode;

    $target_entity_type = $entity->field_activity_entity->target_type;

    // Change view mode for posts.
    if ($target_entity_type === 'post') {
      $view_mode = 'render_entity';
    }

    return $view_mode;
  }
}

<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\ActivityDestination\StreamHomeActivityDestination.
 */

namespace Drupal\activity_creator\Plugin\ActivityDestination;
use Drupal\activity_creator\Plugin\ActivityDestinationBase;

/**
 * Provides a 'StreamHomeActivityDestination' acitivy destination.
 *
 * @ActivityDestination(
 *  id = "stream_home",
 *  label = @Translation("Stream (home)"),
 * )
 */
class StreamHomeActivityDestination extends ActivityDestinationBase {

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

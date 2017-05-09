<?php

/**
 * @file
 * Contains \Drupal\activity_basics\Plugin\ActivityDestination\StreamExploreActivityDestination.
 */

namespace Drupal\activity_basics\Plugin\ActivityDestination;
use Drupal\activity_creator\Plugin\ActivityDestinationBase;

/**
 * Provides a 'StreamExploreActivityDestination' acitivy destination.
 *
 * @ActivityDestination(
 *  id = "stream_explore",
 *  label = @Translation("Stream (explore)"),
 *  is_aggregatable = TRUE,
 *  is_common = TRUE,
 * )
 */
class StreamExploreActivityDestination extends ActivityDestinationBase {

}

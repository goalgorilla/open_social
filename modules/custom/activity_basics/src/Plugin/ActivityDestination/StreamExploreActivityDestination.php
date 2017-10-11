<?php

namespace Drupal\activity_basics\Plugin\ActivityDestination;

use Drupal\activity_creator\Plugin\ActivityDestinationBase;

/**
 * Provides a 'StreamExploreActivityDestination' acitivy destination.
 *
 * @ActivityDestination(
 *  id = "stream_explore",
 *  label = @Translation("Stream (explore)"),
 *  isAggregatable = TRUE,
 *  isCommon = TRUE,
 * )
 */
class StreamExploreActivityDestination extends ActivityDestinationBase {

}

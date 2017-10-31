<?php

namespace Drupal\activity_basics\Plugin\ActivityDestination;

use Drupal\activity_creator\Plugin\ActivityDestinationBase;

/**
 * Provides a 'StreamHomeActivityDestination' acitivy destination.
 *
 * @ActivityDestination(
 *  id = "stream_home",
 *  label = @Translation("Stream (home)"),
 *  isAggregatable = TRUE,
 *  isCommon = TRUE,
 * )
 */
class StreamHomeActivityDestination extends ActivityDestinationBase {

}

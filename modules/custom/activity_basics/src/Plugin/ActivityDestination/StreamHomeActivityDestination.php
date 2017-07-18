<?php

namespace Drupal\activity_basics\Plugin\ActivityDestination;

use Drupal\activity_creator\Plugin\ActivityDestinationBase;

/**
 * Provides a 'StreamHomeActivityDestination' acitivy destination.
 *
 * @ActivityDestination(
 *  id = "stream_home",
 *  label = @Translation("Stream (home)"),
 *  is_aggregatable = TRUE,
 *  is_common = TRUE,
 * )
 */
class StreamHomeActivityDestination extends ActivityDestinationBase {

}

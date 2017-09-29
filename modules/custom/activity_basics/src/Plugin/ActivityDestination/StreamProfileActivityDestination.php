<?php

namespace Drupal\activity_basics\Plugin\ActivityDestination;

use Drupal\activity_creator\Plugin\ActivityDestinationBase;

/**
 * Provides a 'StreamProfileActivityDestination' acitivy destination.
 *
 * @ActivityDestination(
 *  id = "stream_profile",
 *  label = @Translation("Stream (profile)"),
 *  isAggregatable = TRUE,
 *  isCommon = FALSE,
 * )
 */
class StreamProfileActivityDestination extends ActivityDestinationBase {

}

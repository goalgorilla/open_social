<?php

namespace Drupal\activity_basics\Plugin\ActivityDestination;

use Drupal\activity_creator\Plugin\ActivityDestinationBase;

/**
 * Provides a 'StreamGroupActivityDestination' acitivy destination.
 *
 * @ActivityDestination(
 *  id = "stream_group",
 *  label = @Translation("Stream (group)"),
 *  is_aggregatable = TRUE,
 *  is_common = TRUE,
 * )
 */
class StreamGroupActivityDestination extends ActivityDestinationBase {

}

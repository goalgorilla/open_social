<?php

/**
 * @file
 * Contains \Drupal\activity_basics\Plugin\ActivityDestination\StreamProfileActivityDestination.
 */

namespace Drupal\activity_basics\Plugin\ActivityDestination;
use Drupal\activity_creator\Plugin\ActivityDestinationBase;

/**
 * Provides a 'StreamProfileActivityDestination' acitivy destination.
 *
 * @ActivityDestination(
 *  id = "stream_profile",
 *  label = @Translation("Stream (profile)"),
 *  is_aggregatable = TRUE,
 *  is_common = FALSE,
 * )
 */
class StreamProfileActivityDestination extends ActivityDestinationBase {

}

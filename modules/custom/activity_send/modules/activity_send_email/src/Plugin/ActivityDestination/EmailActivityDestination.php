<?php

/**
 * @file
 * Contains \Drupal\activity_send_email\Plugin\ActivityDestination\EmailActivityDestination.
 */

namespace Drupal\activity_send_email\Plugin\ActivityDestination;

use Drupal\activity_creator\Plugin\ActivityDestinationBase;

/**
 * Provides a 'EmailActivityDestination' activity destination.
 *
 * @ActivityDestination(
 *  id = "email",
 *  label = @Translation("Email"),
 * )
 */
class EmailActivityDestination extends ActivityDestinationBase {

}

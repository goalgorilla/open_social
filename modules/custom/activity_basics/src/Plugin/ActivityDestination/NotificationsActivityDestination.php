<?php

namespace Drupal\activity_basics\Plugin\ActivityDestination;

use Drupal\activity_creator\Plugin\ActivityDestinationBase;

/**
 * Provides a 'NotificationsActivityDestination' acitivy destination.
 *
 * @ActivityDestination(
 *  id = "notifications",
 *  label = @Translation("Notifications"),
 *  isAggregatable = FALSE,
 *  isCommon = FALSE,
 * )
 */
class NotificationsActivityDestination extends ActivityDestinationBase {

}

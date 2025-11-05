<?php

/**
 * @file
 * Hooks provided by the Activity Send push module.
 */

use Drupal\activity_creator\ActivityInterface;
use Drupal\Core\Url;
use Drupal\message\MessageInterface;

/**
 * Alter the URL for push notification.
 *
 * @param \Drupal\Core\Url $url
 *   The URL of push notification.
 * @param \Drupal\activity_creator\ActivityInterface $activity
 *   Activity entity.
 * @param \Drupal\message\MessageInterface $message
 *   Message entity.
 */
function hook_activity_send_push_url_alter(Url $url, ActivityInterface $activity, MessageInterface $message) {}

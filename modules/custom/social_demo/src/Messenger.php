<?php

namespace Drupal\social_demo;

use Drupal\Core\Messenger\Messenger as MessengerBase;

/**
 * Provide a Messenger service for use during demo content generation.
 *
 * Mutes messages that we don't want users to see that can occur when
 * social_demo creates demo content on the platform.
 *
 * This class is intentionally broad and assumes the Social Demo module is
 * disabled after creating demo content.
 */
class Messenger extends MessengerBase {

  /**
   * {@inheritdoc}
   */
  public function addMessage($message, $type = MessengerBase::TYPE_STATUS, $repeat = FALSE) {
    // Skip all status messages while the demo content module is enabled.
    // This avoids follow and enrollment messages that are not needed for the
    // admin. The demo content module provides its own way of showing progress
    // in drush.
    if ($type === MessengerBase::TYPE_STATUS) {
      return $this;
    }

    return MessengerBase::addMessage($message, $type, $repeat);
  }

}

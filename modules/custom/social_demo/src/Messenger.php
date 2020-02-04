<?php

namespace Drupal\social_demo;

use Drupal\Core\Messenger\Messenger as MessengerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provide a Messenger service for use during demo content generation.
 *
 * Mutes messages that we don't want users to see that can occur when
 * social_demo creates demo content on the platform.
 */
class Messenger extends MessengerBase {

  /**
   * {@inheritdoc}
   */
  public function addMessage($message, $type = MessengerBase::TYPE_STATUS, $repeat = FALSE) {
    // Skip messages from social_follow_content that are created when the demo
    // module is enabled.
    if ($type === 'status' && $message instanceof TranslatableMarkup && strpos($message->getUntranslatedString(), 'You are now automatically following this ') === 0) {
      return $this;
    }

    return MessengerBase::addMessage($message, $type, $repeat);
  }

}

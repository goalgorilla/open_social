<?php

namespace Drupal\social_swiftmail\Plugin\Mail;

use Drupal\Core\Render\Markup;
use Drupal\Core\Site\Settings;
use Drupal\swiftmailer\Plugin\Mail\SwiftMailer;

/**
 * Provides a 'Forced HTML SwiftMailer' plugin to send emails.
 *
 * @Mail(
 *   id = "social_swiftmailer",
 *   label = @Translation("Social Swift Mailer"),
 *   description = @Translation("Forces the given body text to be interpreted as
 *   HTML.")
 * )
 */
class SocialSwiftMailer extends SwiftMailer {

  /**
   * Massages the message body into the format expected for rendering.
   *
   * @param array $message
   *   The message.
   *
   * @return array
   *   The massaged message.
   */
  public function massageMessageBody(array $message) {
    return parent::massageMessageBody($message);
  }

}

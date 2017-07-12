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

    // @see: SwiftMailer::massageMessageBody()
    $line_endings = Settings::get('mail_line_endings', PHP_EOL);
    $message['body'] = Markup::create(implode($line_endings, array_map(function ($body) {
      // If the field contains no html tags we can assume newlines will need be
      // converted to <br>.
      if (strlen(strip_tags($body)) === strlen($body)) {
        $body = str_replace("\r", '', $body);
        $body = str_replace("\n", '<br>', $body);
      }
      return check_markup($body, 'full_html');
    }, $message['body'])));
    return $message;
  }

}

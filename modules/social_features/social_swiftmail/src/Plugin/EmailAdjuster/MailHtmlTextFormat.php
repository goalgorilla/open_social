<?php

namespace Drupal\social_swiftmail\Plugin\EmailAdjuster;

use Drupal\symfony_mailer\Annotation\EmailAdjuster;
use Drupal\symfony_mailer\EmailInterface;
use Drupal\symfony_mailer\Processor\EmailAdjusterBase;

/**
 * Defines the send mail markup plugin.
 *
 * @EmailAdjuster(
 *   id = "mail_html_text_format",
 *   label = @Translation("Apply mail_html to body text"),
 *   description = @Translation("Make sure body text has mail_html format."),
 *   weight = 900,
 * )
 */
class MailHtmlTextFormat extends EmailAdjusterBase {

  /**
   * @inheritdoc
   */
  function build(EmailInterface $email): void {
    $body = $email->getBody();

    // Make sure we have a fallback for our processed emails through Cron
    // Sometimes they, for some reason, lose the formatting which means
    // they will be escaped.
    if (!empty($body[0]['#type']) && $body[0]['#type'] === 'processed_text') {
      if (empty($body[0]['#format'])) {
        $body[0]['#format'] = 'mail_html';
      }
    }

    $email->setBody($body);
  }

}

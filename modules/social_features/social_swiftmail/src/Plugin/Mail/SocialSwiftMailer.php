<?php

namespace Drupal\social_swiftmail\Plugin\Mail;

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

}

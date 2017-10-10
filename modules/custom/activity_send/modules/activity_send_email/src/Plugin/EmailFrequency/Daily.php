<?php

namespace Drupal\activity_send_email\Plugin\EmailFrequency;

use Drupal\activity_send_email\EmailFrequencyBase;

/**
 * Define a concrete class for a immediate emails.
 *
 * @EmailFrequency(
 *   id = "daily",
 *   name = @Translation("Send e-mails in a daily digest"),
 *   interval = 0
 * )
 */
class Daily extends EmailFrequencyBase {


}

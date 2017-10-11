<?php

namespace Drupal\activity_send_email\Plugin\EmailFrequency;

use Drupal\activity_send_email\EmailFrequencyBase;

/**
 * Define a concrete class for daily emails.
 *
 * @EmailFrequency(
 *   id = "daily",
 *   name = @Translation("Daily"),
 *   weight = 20
 * )
 */
class Daily extends EmailFrequencyBase {}

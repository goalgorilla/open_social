<?php

namespace Drupal\activity_send_email\Plugin\EmailFrequency;

use Drupal\activity_send_email\EmailFrequencyBase;

/**
 * Define a concrete class for weekly emails.
 *
 * @EmailFrequency(
 *   id = "weekly",
 *   name = @Translation("Weekly"),
 *   weight = 30,
 *   interval = 604800
 * )
 */
class Weekly extends EmailFrequencyBase {}

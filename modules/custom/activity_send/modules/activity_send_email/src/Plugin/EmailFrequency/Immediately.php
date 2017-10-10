<?php

namespace Drupal\activity_send_email\Plugin\EmailFrequency;

use Drupal\activity_send_email\EmailFrequencyBase;

/**
 * Define a concrete class for immediate emails.
 *
 * @EmailFrequency(
 *   id = "immediately",
 *   name = @Translation("Immediately"),
 *   interval = 0
 * )
 */
class Immediately extends EmailFrequencyBase {}

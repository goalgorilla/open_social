<?php

namespace Drupal\activity_send_email\Plugin\EmailFrequency;

use Drupal\activity_send_email\EmailFrequencyBase;

/**
 * Define a concrete class for no emails.
 *
 * @EmailFrequency(
 *   id = "none",
 *   name = @Translation("- None -"),
 *   weight = 0
 * )
 */
class None extends EmailFrequencyBase {}

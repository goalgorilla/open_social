<?php

namespace Drupal\activity_send_email\Plugin\EmailFrequency;

use Drupal\activity_creator\Entity\Activity;
use Drupal\activity_send_email\EmailFrequencyBase;
use Drupal\message\Entity\Message;
use Drupal\user\Entity\User;

/**
 * Define a concrete class for no emails.
 *
 * @EmailFrequency(
 *   id = "none",
 *   name = @Translation("- None -"),
 *   weight = 0,
 *   interval = 0
 * )
 */
class None extends EmailFrequencyBase {

  /**
   * {@inheritdoc}
   */
  public function processItem(Activity $activity, Message $message, User $target) {}

}

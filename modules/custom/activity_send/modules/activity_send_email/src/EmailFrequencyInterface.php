<?php

namespace Drupal\activity_send_email;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\activity_creator\Entity\Activity;
use Drupal\message\Entity\Message;
use Drupal\user\Entity\User;

/**
 * Common interface for all email frequencies.
 */
interface EmailFrequencyInterface extends PluginInspectionInterface {

  /**
   * Return the name of the email frequency.
   *
   * @return string
   *   The name of the email frequency.
   */
  public function getName();

  /**
   * Return the weight of the frequency option.
   *
   * @return int
   *   The weight of the frequency option.
   */
  public function getWeight();

  /**
   * Return the interval of the email frequency in seconds.
   *
   * @return int
   *   The interval in seconds.
   */
  public function getInterval();

  /**
   * Processes an activity item.
   *
   * @param \Drupal\activity_creator\Entity\Activity $activity
   *   The Activity object.
   * @param \Drupal\message\Entity\Message $message
   *   The Message object.
   * @param \Drupal\user\Entity\User $target
   *   The target user account.
   */
  public function processItem(Activity $activity, Message $message, User $target);

}

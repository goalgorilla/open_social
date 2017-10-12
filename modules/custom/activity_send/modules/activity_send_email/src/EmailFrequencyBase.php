<?php

namespace Drupal\activity_send_email;

use Drupal\activity_creator\Entity\Activity;
use Drupal\Component\Plugin\PluginBase;
use Drupal\message\Entity\Message;
use Drupal\user\Entity\User;

/**
 * Class EmailFrequencyBase.
 *
 * Implements common functions for all EmailFrequency classes.
 */
class EmailFrequencyBase extends PluginBase implements EmailFrequencyInterface {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->pluginDefinition['weight'];
  }

  /**
   * {@inheritdoc}
   */
  public function processItem(Activity $activity, Message $message, User $target) {}

}

<?php

namespace Drupal\activity_send_email;

use Drupal\activity_creator\Entity\Activity;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Database\Database;
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
  public function getInterval() {
    return $this->pluginDefinition['interval'];
  }

  /**
   * {@inheritdoc}
   */
  public function processItem(Activity $activity, Message $message, User $target) {
    $db = Database::getConnection();

    // Insert incoming activities in our digest table.
    $db->insert('user_activity_digest')
      ->fields([
        'uid',
        'activity',
        'frequency',
        'timestamp',
      ])
      ->values([
        $target->id(),
        $activity->id(),
        $this->pluginId,
        time(),
      ])
      ->execute();
  }

}

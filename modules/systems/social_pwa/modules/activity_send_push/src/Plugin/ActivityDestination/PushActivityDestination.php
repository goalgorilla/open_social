<?php

namespace Drupal\activity_send_push\Plugin\ActivityDestination;

use Drupal\activity_send\Plugin\SendActivityDestinationBase;
use Drupal\message\Entity\Message;

/**
 * Provides a 'PushActivityDestination' activity destination.
 *
 * @ActivityDestination(
 *  id = "push",
 *  label = @Translation("Web Push"),
 * )
 */
class PushActivityDestination extends SendActivityDestinationBase {

  /**
   * {@inheritdoc}
   */
  public static function getSendPushMessageTemplates() {
    return parent::getSendMessageTemplates('push');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSendPushUserSettings($account) {
    return parent::getSendUserSettings('push', $account);
  }

  /**
   * {@inheritdoc}
   */
  public static function setSendPushUserSettings($account, $values) {
    parent::setSendUserSettings('push', $account, $values);
  }

  /**
   * Get field value for 'output_text' field from data array.
   */
  public static function getSendPushOutputText(Message $message) {
    $text = NULL;
    if (isset($message)) {
      $activity_factory = \Drupal::service('activity_creator.activity_factory');
      $value = $activity_factory->getMessageText($message);
      // Text for email.
      $text = $value[0];
    }

    return $text;
  }

}

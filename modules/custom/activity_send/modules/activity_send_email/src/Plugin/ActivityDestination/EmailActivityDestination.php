<?php

/**
 * @file
 * Contains \Drupal\activity_send_email\Plugin\ActivityDestination\EmailActivityDestination.
 */

namespace Drupal\activity_send_email\Plugin\ActivityDestination;

use Drupal\activity_send\Plugin\SendActivityDestinationBase;
use Drupal\message\Entity\Message;

/**
 * Provides a 'EmailActivityDestination' activity destination.
 *
 * @ActivityDestination(
 *  id = "email",
 *  label = @Translation("Email"),
 * )
 */
class EmailActivityDestination extends SendActivityDestinationBase {

  /**
   * {@inheritdoc}
   */
  public static function getSendEmailMessageTemplates() {
    return parent::getSendMessageTemplates('email');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSendEmailUserSettings($account) {
    return parent::getSendUserSettings('email', $account);
  }

  /**
   * {@inheritdoc}
   */
  public static function setSendEmailUserSettings($account, $values) {
    parent::setSendUserSettings('email', $account, $values);
  }

  /**
   * Get field value for 'output_text' field from data array.
   */
  public static function getSendEmailOutputText(Message $message) {
    $text = NULL;
    if (isset($message)) {
      $value = $message->getText();
      if (empty($value)) {
        $activity_factory = \Drupal::service('activity_creator.activity_factory');
        $value = $activity_factory->getMessageText($message);
      }
      // Text for email.
      if (!empty($value[2])) {
        $text = $value[2];
      }
      // Default text.
      else {
        $text = $value[0];
      }
    }

    return $text;
  }

}

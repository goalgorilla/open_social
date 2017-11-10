<?php

namespace Drupal\activity_send_email\Plugin\ActivityDestination;

use Drupal\activity_send\Plugin\SendActivityDestinationBase;
use Drupal\message\Entity\Message;

/**
 * Provides a 'EmailActivityDestination' activity destination.
 *
 * @ActivityDestination(
 *  id = "email",
 *  label = @Translation("Email"),
 *  isAggregatable = FALSE,
 *  isCommon = FALSE,
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
   *
   * @param \Drupal\message\Entity\Message $message
   *   The Message object.
   *
   * @return string|null
   *   If we have message text we return the text, otherwise null.
   */
  public static function getSendEmailOutputText(Message $message) {
    $text = NULL;
    if (isset($message)) {
      $activity_factory = \Drupal::service('activity_creator.activity_factory');
      $value = $activity_factory->getMessageText($message);
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

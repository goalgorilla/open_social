<?php

namespace Drupal\activity_send_email\Plugin\ActivityDestination;

use Drupal\activity_send\Plugin\SendActivityDestinationBase;
use Drupal\message\Entity\Message;
use Drupal\social_user\Entity\User;

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
  public static function getSendEmailMessageTemplates(): array {
    return parent::getSendMessageTemplates('email');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSendEmailUserSettings(User $account): array {
    return parent::getSendUserSettings('email', $account);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSendEmailAllUsersSetting(array $account_ids, string $message_template_id): array {
    return parent::getSendAllUsersSetting('email', $account_ids, $message_template_id);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSendEmailUsersIdsByFrequency(array $account_ids, string $message_template_id, string $frequency = 'immediately'): array {
    return parent::getSendUserIdsByFrequency('email', $account_ids, $frequency, $message_template_id);
  }

  /**
   * {@inheritdoc}
   */
  public static function setSendEmailUserSettings(User $account, array $values): void {
    parent::setSendUserSettings('email', $account, $values);
  }

  /**
   * Get field value for 'output_text' field from data array.
   *
   * @param \Drupal\message\Entity\Message $message
   *   The Message object.
   * @param string $langcode
   *   The language in which we need to get the email text.
   *
   * @return string|null
   *   If we have message text we return the text, otherwise null.
   */
  public static function getSendEmailOutputText(Message $message, string $langcode = ''): ?string {
    $activity_factory = \Drupal::service('activity_creator.activity_factory');
    $value = $activity_factory->getMessageText($message, $langcode);

    // Text for email.
    if (!empty($value[2]) && is_string($value[2])) {
      $text = $value[2];
    }
    // Default text.
    else {
      $text = $value[0];
    }

    return is_string($text) ? $text : NULL;
  }

}

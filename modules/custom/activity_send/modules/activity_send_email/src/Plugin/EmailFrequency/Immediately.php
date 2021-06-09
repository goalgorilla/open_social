<?php

namespace Drupal\activity_send_email\Plugin\EmailFrequency;

use Drupal\activity_creator\Entity\Activity;
use Drupal\activity_send_email\EmailFrequencyBase;
use Drupal\activity_send_email\Plugin\ActivityDestination\EmailActivityDestination;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\message\Entity\Message;
use Drupal\user\Entity\User;

/**
 * Define a concrete class for immediate emails.
 *
 * @EmailFrequency(
 *   id = "immediately",
 *   name = @Translation("Immediately"),
 *   weight = 10,
 *   interval = 0
 * )
 */
class Immediately extends EmailFrequencyBase {

  /**
   * {@inheritdoc}
   */
  public function processItem(Activity $activity, Message $message, User $target, $body_text = NULL) {
    // If the user is blocked, we don't want to process this item further.
    if ($target->isBlocked()) {
      return;
    }

    // Continue if we have text to send and the user is currently offline.
    if (isset($activity->field_activity_output_text) && EmailActivityDestination::isUserOffline($target)) {
      $langcode = $target->getPreferredLangcode();

      // If no body text is provided, get it from message for given language.
      if (!$body_text) {
        $body_text = EmailActivityDestination::getSendEmailOutputText($message, $langcode);
      }

      if ($langcode && !empty($body_text)) {
        $this->sendEmail($body_text, $langcode, $target);
      }
    }
  }

  /**
   * Send an email with a single notification.
   *
   * @param string $body_text
   *   The text to send to the target user.
   * @param string $langcode
   *   The langcode of the target user.
   * @param \Drupal\user\Entity\User $target
   *   The target account to send the email to.
   */
  protected function sendEmail(string $body_text, string $langcode, User $target) {
    // Translating frequency instance in the language of the user.
    // @codingStandardsIgnoreStart
    $frequency_translated = t($this->getName()->getUntranslatedString(), [], ['langcode' => $langcode]);
    // @codingStandardsIgnoreEnd

    // Construct the render array.
    $notification = [
      '#theme' => 'directmail',
      '#notification' => $body_text,
      '#notification_settings' => t('Based on your @settings, the notification above is sent to you <strong>:frequency</strong>', [
        '@settings' => Link::fromTextAndUrl(t('email notification settings', [], ['langcode' => $langcode]), Url::fromRoute('activity_send_email.user_edit_page')->setAbsolute())->toString(),
        ':frequency' => $frequency_translated,
      ],
      ['langcode' => $langcode]),
    ];

    $params['body'] = \Drupal::service('renderer')->renderRoot($notification);

    // Send the email.
    $mail_manager = \Drupal::service('plugin.manager.mail');
    $mail_manager->mail(
      'activity_send_email',
      'activity_send_email',
      $target->getEmail(),
      $langcode,
      $params,
      NULL,
      TRUE
    );
  }

}

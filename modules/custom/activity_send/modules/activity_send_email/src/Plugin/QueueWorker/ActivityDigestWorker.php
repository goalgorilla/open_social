<?php

namespace Drupal\activity_send_email\Plugin\QueueWorker;

use Drupal\activity_send\Plugin\QueueWorker\ActivitySendWorkerBase;
use Drupal\activity_creator\Entity\Activity;
use Drupal\activity_send_email\Plugin\ActivityDestination\EmailActivityDestination;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\message\Entity\Message;
use Drupal\user\Entity\User;

/**
 * An activity send email worker.
 *
 * @QueueWorker(
 *   id = "activity_digest_worker",
 *   title = @Translation("Process activity_digest_worker queue."),
 *   cron = {"time" = 60}
 * )
 *
 * This QueueWorker is responsible for sending emails from the queue
 */
class ActivityDigestWorker extends ActivitySendWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!empty($data['uid']) && !empty($data['frequency']) && !empty($data['activities'])) {
      // Get target account.
      $target = User::load($data['uid']);

      // Make sure we have an actual user account to work with.
      if ($target instanceof User) {
        $langcode = $target->getPreferredLangcode();
        $digest_notifications = [
          '#theme' => 'digestmail',
        ];

        foreach ($data['activities'] as $activity_id) {
          $activity = Activity::load($activity_id);

          // Continue if we have text to send.
          if (isset($activity->field_activity_output_text)) {
            // Load the message.
            $message = Message::load($activity->field_activity_message->target_id);
            $body_text = EmailActivityDestination::getSendEmailOutputText($message, $langcode);

            if ($langcode && !empty($body_text)) {
              $digest_notifications['#notifications'][] = $body_text;
            }
          }
        }

        // If we have notification to send continue preparing the email.
        if (!empty($digest_notifications['#notifications'])) {
          $notification_count = count($digest_notifications['#notifications']);

          // Get the notification count for the email template.
          $digest_notifications['#notification_count'] = \Drupal::translation()->formatPlural($notification_count, 'You have received <strong>:count</strong> notification', 'You have received <strong>:count</strong> notifications', [':count' => $notification_count], ['langcode' => $langcode]);

          $emailfrequencymanager = \Drupal::service('plugin.manager.emailfrequency');
          /* @var \Drupal\activity_send_email\EmailFrequencyInterface $instance */
          $instance = $emailfrequencymanager->createInstance($data['frequency']);

          // Translating frequency instance in the language of the user.
          // @codingStandardsIgnoreStart
          $frequency_translated = t($instance->getName()->getUntranslatedString(), [], ['langcode' => $langcode]);
          // @codingStandardsIgnoreEnd

          // Get the notification settings for the email template.
          $digest_notifications['#notification_settings'] = \Drupal::translation()->formatPlural($notification_count, 'Based on your @settings, the notification above is sent to you as a <strong>:frequency mail</strong>', 'Based on your @settings, the notifications above are sent to you as a <strong>:frequency mail</strong>', [
            '@settings' => Link::fromTextAndUrl(t('email notification settings'), Url::fromRoute('entity.user.edit_form', ['user' => $target->id()])->setAbsolute())->toString(),
            ':frequency' => $frequency_translated,
          ],
          ['langcode' => $langcode]);

          // Render the notifications using the digestmail.html.twig template.
          $params['body'] = \Drupal::service('renderer')->renderRoot($digest_notifications);

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
    }
  }

}

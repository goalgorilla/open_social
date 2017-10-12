<?php

namespace Drupal\activity_send_email\Plugin\QueueWorker;

use Drupal\activity_send\Plugin\QueueWorker\ActivitySendWorkerBase;
use Drupal\activity_creator\Entity\Activity;
use Drupal\activity_send_email\Plugin\ActivityDestination\EmailActivityDestination;
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
        $params['body'] = '';

        foreach ($data['activities'] as $activity_id) {
          $activity = Activity::load($activity_id);

          // Continue if we have text to send.
          if (isset($activity->field_activity_output_text)) {
            // Load the message.
            $message = Message::load($activity->field_activity_message->target_id);
            $body_text = EmailActivityDestination::getSendEmailOutputText($message);

            if ($langcode && !empty($body_text)) {
              $params['body'] .= $body_text;
            }
          }
        }

        // If we have text to send, let's do it now.
        if (!empty($params['body'])) {
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

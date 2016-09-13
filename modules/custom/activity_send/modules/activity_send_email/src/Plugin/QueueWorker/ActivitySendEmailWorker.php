<?php

/**
 * @file
 * Contains \Drupal\activity_send_email\Plugin\QueueWorker\ActivitySendEmailWorker.
 */

namespace Drupal\activity_send_email\Plugin\QueueWorker;

use Drupal\activity_send_email\Plugin\ActivityDestination\EmailActivityDestination;
use Drupal\activity_send\Plugin\QueueWorker\ActivitySendWorkerBase;
use Drupal\activity_creator\Entity\Activity;
use Drupal\message\Entity\Message;


/**
 * An activity send email worker.
 *
 * @QueueWorker(
 *   id = "activity_send_email_worker",
 *   title = @Translation("Process activity_send_email queue."),
 *   cron = {"time" = 60}
 * )
 *
 * This QueueWorker is responsible for sending emails from the queue
 */
class ActivitySendEmailWorker extends ActivitySendWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {

    // First make sure it's an actual Activity entity.
    if ($activity = Activity::load($data['entity_id'])) {
      // Get target account.
      $target_account = EmailActivityDestination::getSendTargetUser($activity);
      // Check if user last activity was more than few minutes ago.
      if (is_object($target_account) && EmailActivityDestination::isUserOffline($target_account)) {
        // Get Message Template id.
        $message = Message::load($activity->field_activity_message->target_id);
        $message_template_id = $message->getTemplate()->id();

        // Get email notification settings of active user.
        $user_email_settings = EmailActivityDestination::getSendEmailUserSettings($target_account);

        // Check if email notifications is enabled for this kind of activity.
        if (!empty($user_email_settings[$message_template_id]) && isset($activity->field_activity_output_text)) {
          // Send Email
          $langcode = \Drupal::currentUser()->getPreferredLangcode();
          $params['body'] = EmailActivityDestination::getSendEmailOutputText($message);

          $mail_manager = \Drupal::service('plugin.manager.mail');
          $mail = $mail_manager->mail(
            'activity_send_email',
            $message_template_id,
            $target_account->getEmail(),
            $langcode,
            $params,
            $reply = NULL,
            $send = TRUE
          );
        }
      }
    }

  }

}

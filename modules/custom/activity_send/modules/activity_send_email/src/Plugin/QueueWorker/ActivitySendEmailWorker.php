<?php

/**
 * @file
 * Contains \Drupal\activity_send_email\Plugin\QueueWorker\ActivitySendEmailWorker.
 */

namespace Drupal\activity_send_email\Plugin\QueueWorker;

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
      if (isset($activity->field_activity_recipient_user)) {
        $target_id = $activity->field_activity_recipient_user->target_id;
        $target_account = \Drupal::entityTypeManager()
          ->getStorage('user')
          ->load($target_id);

        // Check if user last activity was more than few minutes ago.
        if (!empty($target_account->id())) {
          $query = \Drupal::database()->select('sessions', 's');
          $query->addField('s', 'timestamp');
          $query->condition('s.uid', $target_account->id());
          $timestamp = $query->execute()->fetchField();
          // 5 minutes ago
          $time = REQUEST_TIME - (60 * 5);

          if (!empty($timestamp) && $timestamp < $time) {
            // Get Message Template id.
            $mail = Message::load($activity->field_activity_message->target_id);
            $message_template_id = $mail->getTemplate()->id();

            // Get email notification settings of active user.
            $query = \Drupal::database()->select('user_activity_send', 'uas');
            $query->fields('uas', ['message_template', 'status']);
            $query->condition('uas.destination', 'email');
            $query->condition('uas.uid', $target_account->id());
            $user_email_settings = $query->execute()->fetchAllKeyed();

            // Check if email notifications is enabled for this kind of activity.
            if (!empty($user_email_settings[$message_template_id]) && isset($activity->field_activity_output_text)) {
              // Send Email
              $langcode = \Drupal::currentUser()->getPreferredLangcode();
              $params['body'] = $target_id = $activity->field_activity_output_text->value;

              $mail_manager = \Drupal::service('plugin.manager.mail');
              $mail = $mail_manager->mail(
                'activity_send_email',
                'activity_send_email',
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

  }

}

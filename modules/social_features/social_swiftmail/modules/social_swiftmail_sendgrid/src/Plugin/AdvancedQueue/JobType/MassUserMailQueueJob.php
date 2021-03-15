<?php

namespace Drupal\social_swiftmail_sendgrid\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\social_queue_storage\Entity\QueueStorageEntity;
use Drupal\social_user\Plugin\AdvancedQueue\JobType\UserMailQueueJob;

/**
 * Advanced Queue Job for emails to users by using the Sendgrid Substitions.
 */
class MassUserMailQueueJob extends UserMailQueueJob {

  /**
   * {@inheritdoc}
   */
  public function process(Job $job) {
    try {
      // Get the Job data.
      $data = $job->getPayload();

      // Validate if the queue data is complete before processing.
      if (self::validateQueueItem($data)) {
        $valid_user_names = [];
        $valid_user_addresses = [];
        // Get the email content that needs to be sent.
        /** @var \Drupal\social_queue_storage\Entity\QueueStorageEntity $queue_storage */
        $queue_storage = $this->storage->getStorage('queue_storage_entity')->load($data['mail']);
        // Check if it's from the configured email bundle type.
        if ($queue_storage->bundle() === 'email') {
          // When there are user ID's configured.
          if (!empty($data['users'])) {
            // Load the users that are in the batch.
            $users = $this->storage->getStorage('user')->loadMultiple($data['users']);

            /** @var \Drupal\user\UserInterface $user */
            foreach ($users as $user) {
              // Instead of sending the email, we add the emailaddress to a list
              // for mass sending and let Sendgrid take care of this.
              // As well as adding the display name, to the list of substitutions
              // so Sendgrid can use the correct personal name.
              if ($user->getEmail()) {
                $valid_user_addresses[] = $user->getEmail();
                $valid_user_names[] = $user->getDisplayName();
              }
            }
          }

          // When there are email addresses configured.
          if (!empty($data['user_mail_addresses'])) {
            foreach ($data['user_mail_addresses'] as $mail_address) {
              if ($this->emailValidator->isValid($mail_address['email_address'])) {
                // Instead of sending the email, we add the address to a list
                // for mass sending and let Sendgrid take care of this.
                // Since there is no display name attached that will be ommitted.
                $valid_user_addresses[] = $mail_address['email_address'];
              }
            }
          }

          // Send out an email to all valid email addresses, send as a string
          // comma separated.
          if (!empty($valid_user_addresses)) {
            $all_valid_emails = implode(",", $valid_user_addresses);
            // All data is processed, lets send the email.
            $this->sendMassMail($all_valid_emails, $this->languageManager->getDefaultLanguage()
              ->getId(), $queue_storage, $valid_user_names);
          }
        }
      }

      // By default mark the Job as failed.
      $this->getLogger('user_email_queue')->error('The Job was not finished correctly, no error thrown.');
      return JobResult::failure('The Job was not finished correctly, no error thrown.');
    }
    catch (\Exception $e) {
      $this->getLogger('user_email_queue')->error($e->getMessage());
      return JobResult::failure($e->getMessage());
    }
  }

  /**
   * Send the email.
   *
   * @param string $valid_user_addresses
   *   The recipients as a string of email addresses comma separated.
   * @param string $langcode
   *   The recipient language.
   * @param \Drupal\social_queue_storage\Entity\QueueStorageEntity $queue_storage
   *   The email content from the storage entity.
   * @param array $valid_user_names
   *   The recipients including email address and display name if available.
   */
  protected function sendMassMail(string $valid_user_addresses, string $langcode, QueueStorageEntity $queue_storage, array $valid_user_names) {
    // Lets build the mail for SendGrid.
    $params = [
      'subject' => $queue_storage->get('field_subject')->value,
      'message' => check_markup($queue_storage->get('field_message')->value, 'mail_html'),
    ];

    // Add sendgrid specific data to the params.
    if (!empty($valid_user_names)) {
      // The key of the array is the needle in the haystack of the substitution.
      // also see social_swiftmail_sendgrid_token_alter.
      $params['sendgrid']['substitutions']['{{social_user:recipient}}'] = $valid_user_names;
      // Send it as context so Drupal understands we want to token_replace it.
      $params['social_user:recipient'] = $valid_user_names;
    }

    // The from address.
    $from = $queue_storage->get('field_reply_to')->value;

    // Lets treat our users as BCC so we can send all emails in one go.
    $params['sendgrid']['smtpapito']['bcc'] = TRUE;
    // Lets pass along our context as requested in the message / hook_mail
    // functions.
    $context = ['context' => $params];
    try {
      // Use the this modules mail function for better token replacements.
      $this->mailManager->mail('social_swiftmail_sendgrid', 'action_send_mass_email', $valid_user_addresses, $langcode, $context, $from);

      // Try to save a the storage entity to update the finished status.
      // to let our queue storage know we're golden.
      $queue_storage->setFinished(TRUE);
      $queue_storage->save();
    }
    catch (EntityStorageException $e) {
      $this->getLogger('mass_user_email_queue')->error($e->getMessage());
    }
  }

  /**
   * Validate the queue item data.
   *
   * Before processing the queue item data we want to check if all the
   * necessary components are available.
   *
   * @param array $data
   *   The content of the queue item.
   *
   * @return bool
   *   True if the item contains all the necessary data.
   */
  private static function validateQueueItem(array $data) {
    // The queue data must contain the 'mail' key and it should either
    // contain 'users' or 'user_mail_addresses'.
    return isset($data['mail'])
      && (isset($data['users']) || isset($data['user_mail_addresses']));
  }

}

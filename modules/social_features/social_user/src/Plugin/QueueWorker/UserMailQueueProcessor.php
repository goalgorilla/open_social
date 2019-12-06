<?php

namespace Drupal\social_user\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Psr\Log\LoggerInterface;

/**
 * Queue worker to process email to users.
 *
 * @QueueWorker(
 *   id = "user_email_queue",
 *   title = @Translation("User email processor"),
 *   cron = {"time" = 60}
 * )
 */
class UserMailQueueProcessor extends QueueWorkerBase {

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MailManagerInterface $mail_manager, LoggerInterface $logger, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mailManager = $mail_manager;
    $this->logger = $logger;
    $this->storage = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!empty($data)) {
      // Get the email content that needs to be sent.
      /** @var \Drupal\social_queue_storage\Entity\QueueStorageEntityInterface $queue_storage */
      $queue_storage = $this->storage->getStorage('queue_storage_entity')->load($data['mail']);
      if ($queue_storage->bundle() === 'mail') {
        // Load the users that are in the batch.
        $users = $this->storage->getStorage('user')->loadMultiple($data['users']);

        /** @var \Drupal\user\UserInterface $user */
        foreach ($users as $user) {
          // Send mail.
          $message = $this->mailManager->mail('system', 'action_send_email', $user->getEmail(), $user->language()->getId(), [
            'context' => [
              'subject' => $queue_storage->get('field_subject')->value,
              'message' => $queue_storage->get('field_message')->value,
            ],
          ], $queue_storage->get('field_reply_to')->value);
          // Error logging is handled by \Drupal\Core\Mail\MailManager::mail().
          if ($message['result']) {
            $this->logger->notice('Sent email to %recipient', [
              '%recipient' => $user->getEmail(),
            ]);
          }
        }
      }
    }
  }

}

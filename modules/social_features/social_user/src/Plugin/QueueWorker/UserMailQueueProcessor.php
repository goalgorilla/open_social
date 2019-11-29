<?php

namespace Drupal\social_user\Plugin\QueueWorker;

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
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MailManagerInterface $mail_manager, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mailManager = $mail_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $message = $this->mailManager->mail('system', 'action_send_email', $data['email_address'], $data['lang_code'], $data['params'], $data['config']);
    // Error logging is handled by \Drupal\Core\Mail\MailManager::mail().
    if ($message['result']) {
      $this->logger->notice('Sent email to %recipient', [
        '%recipient' => $data['email_address'],
      ]);
    }
  }

}

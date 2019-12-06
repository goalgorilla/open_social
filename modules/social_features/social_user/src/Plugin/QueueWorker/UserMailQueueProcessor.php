<?php

namespace Drupal\social_user\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queue worker to process email to users.
 *
 * @QueueWorker(
 *   id = "user_email_queue",
 *   title = @Translation("User email processor"),
 *   cron = {"time" = 60}
 * )
 */
class UserMailQueueProcessor extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MailManagerInterface $mail_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mailManager = $mail_manager;
    $this->storage = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.mail'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!empty($data) && isset($data['mail'], $data['users'])) {
      // Get the email content that needs to be sent.
      /** @var \Drupal\social_queue_storage\Entity\QueueStorageEntityInterface $queue_storage */
      $queue_storage = $this->storage->getStorage('queue_storage_entity')->load($data['mail']);
      // Check if it's from the configured email bundle type.
      if ($queue_storage->bundle() === 'email') {
        // Load the users that are in the batch.
        $users = $this->storage->getStorage('user')->loadMultiple($data['users']);

        /** @var \Drupal\user\UserInterface $user */
        foreach ($users as $user) {
          // Attempt sending mail.
          $this->mailManager->mail('system', 'action_send_email', $user->getEmail(), $user->language()->getId(), [
            'context' => [
              'subject' => $queue_storage->get('field_subject')->value,
              'message' => $queue_storage->get('field_message')->value,
            ],
          ], $queue_storage->get('field_reply_to')->value);
        }
      }
    }
  }

}

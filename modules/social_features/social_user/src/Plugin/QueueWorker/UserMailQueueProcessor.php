<?php

namespace Drupal\social_user\Plugin\QueueWorker;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\private_message\Entity\PrivateMessage;
use Drupal\private_message\Service\PrivateMessageService;
use Drupal\user\Entity\User;
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

  use LoggerChannelTrait;
  use StringTranslationTrait;

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
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The private message service.
   *
   * @var \Drupal\private_message\Service\PrivateMessageService
   */
  protected $privateMessage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MailManagerInterface $mail_manager, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation, Connection $database, PrivateMessageService $private_message) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mailManager = $mail_manager;
    $this->storage = $entity_type_manager;
    $this->connection = $database;
    $this->privateMessage = $private_message;
    $this->setStringTranslation($string_translation);
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
      $container->get('entity_type.manager'),
      $container->get('string_translation'),
      $container->get('database'),
      $container->get('private_message.service')
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

        // Check if this is the last item.
        if ($this->lastItem($data['mail'])) {
          // Send the creator a private message that the job is done.
          $recipient = User::load($queue_storage->getOwner()->id());
          $this->sendMessage($recipient, $queue_storage->get('field_subject')->value);
        }
      }
    }
  }

  /**
   * Check if this item is last.
   *
   * @param string $mail_id
   *   The email ID that is in the batch.
   *
   * @return int
   *   The remaining number.
   */
  protected function lastItem($mail_id) {
    // Escape the condition values.
    $item_type = $this->connection->escapeLike('mail');
    $item_id = $this->connection->escapeLike($mail_id);

    // Get all queue items from the queue worker.
    $query = $this->connection->select('queue', 'q');
    $query->fields('q', ['data', 'name']);
    // Plugin name is queue name.
    $query->condition('q.name', 'user_email_queue');
    // Add conditions for the item type and item mail id's.
    // This is not exact but an educated guess as there can be user id's in the
    // data that could contain the item id.
    $query->condition('q.data', '%' . $item_type . '%', 'LIKE');
    $query->condition('q.data', '%' . $item_id . '%', 'LIKE');
    $results = (int) $query->countQuery()->execute()->fetchField();

    // Return TRUE when last item.
    return !($results !== 1);
  }

  /**
   * Send a PM.
   *
   * @param \Drupal\user\Entity\User $recipient
   *   The recipient user.
   * @param string $subject
   *   The subject of the email that was sent in a batch.
   */
  public function sendMessage(User $recipient, $subject) {
    // We'll use user 1, administrator as a sender.
    $sender = User::load(1);
    if (!empty($subject) && $recipient instanceof User && $sender instanceof User) {
      $members[$sender->id()] = $sender;
      $members[$recipient->id()] = $recipient;
      // Create thread between task and job creator.
      $thread = $this->privateMessage->getThreadForMembers($members);
      // Create a single message.
      $private_message = PrivateMessage::create([
        'owner' => $sender->id(),
        'message' => [
          'value' => $this->getMessage($recipient, $subject),
          'format' => 'basic_html',
        ],
      ]);

      // Try to save a new message.
      try {
        $private_message->save();
      }
      catch (EntityStorageException $e) {
        $this->getLogger('user_email_queue')->error($e->getMessage());
      }
      // Try to add the message to the thread.
      try {
        $thread->addMessage($private_message)->save();
      }
      catch (EntityStorageException $e) {
        $this->getLogger('user_email_queue')->error($e->getMessage());
      }
    }
  }

  /**
   * Create the message for the user.
   *
   * @param \Drupal\user\Entity\User $recipient
   *   The recipient user.
   * @param string $subject
   *   The subject of the email that was sent.
   *
   * @return string
   *   The message.
   */
  public function getMessage(User $recipient, string $subject) {
    // Create a message for the user.
    return $this->t('<strong>(This message is automatically generated)</strong>') . PHP_EOL . t('Dear @recipient_name,', ['@recipient_name' => $recipient->getDisplayName()]) . PHP_EOL . PHP_EOL . t('A background process sending e-mail %subject has just finished.', ['%subject' => $subject]);
  }

}

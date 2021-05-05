<?php

namespace Drupal\social_user\Plugin\QueueWorker;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\social_queue_storage\Entity\QueueStorageEntity;
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
   * The language manager interface.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The Email validator service.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MailManagerInterface $mail_manager, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation, Connection $database, LanguageManagerInterface $language_manager, EmailValidatorInterface $email_validator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mailManager = $mail_manager;
    $this->storage = $entity_type_manager;
    $this->connection = $database;
    $this->setStringTranslation($string_translation);
    $this->languageManager = $language_manager;
    $this->emailValidator = $email_validator;
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
      $container->get('language_manager'),
      $container->get('email.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // Validate if the queue data is complete before processing.
    if (self::validateQueueItem($data)) {
      // Get the email content that needs to be sent.
      /** @var \Drupal\social_queue_storage\Entity\QueueStorageEntity $queue_storage */
      $queue_storage = $this->storage->getStorage('queue_storage_entity')->load($data['mail']);
      // Check if it's from the configured email bundle type.
      if ($queue_storage->bundle() === 'email') {
        // When there are user ID's configured.
        if ($data['users']) {
          // Load the users that are in the batch.
          $users = $this->storage->getStorage('user')->loadMultiple($data['users']);

          /** @var \Drupal\user\UserInterface $user */
          foreach ($users as $user) {
            // Attempt sending mail.
            if ($user->getEmail()) {
              $this->sendMail($user->getEmail(), $user->language()->getId(), $queue_storage, $user->getDisplayName());
            }
          }
        }

        // When there are email addresses configured.
        if ($data['user_mail_addresses']) {
          foreach ($data['user_mail_addresses'] as $mail_address) {
            if ($this->emailValidator->isValid($mail_address['email_address'])) {
              // Attempt sending mail.
              $this->sendMail($mail_address['email_address'], $this->languageManager->getDefaultLanguage()->getId(), $queue_storage, $mail_address['display_name']);
            }
          }
        }

        // Check if this is the last item.
        if ($this->lastItem($data['mail'])) {
          $queue_storage->setFinished(TRUE);

          // Try to save a the storage entity to update the finished status.
          try {
            // Saving the entity and setting it to finished should send
            // a message template.
            $queue_storage->save();
          }
          catch (EntityStorageException $e) {
            $this->getLogger('user_email_queue')->error($e->getMessage());
          }
        }
      }
    }
  }

  /**
   * Send the email.
   *
   * @param string $user_mail
   *   The recipient email address.
   * @param string $langcode
   *   The recipient language.
   * @param \Drupal\social_queue_storage\Entity\QueueStorageEntity $mail_params
   *   The email content from the storage entity.
   * @param string $display_name
   *   In case of anonymous users a display name will be given.
   */
  protected function sendMail(string $user_mail, string $langcode, QueueStorageEntity $mail_params, $display_name = NULL) {
    $context = [
      'subject' => $mail_params->get('field_subject')->value,
      'message' => $mail_params->get('field_message')->value,
    ];

    if ($display_name) {
      $context['display_name'] = $display_name;
    }

    // Attempt sending mail.
    $this->mailManager->mail('system', 'action_send_email', $user_mail, $langcode, [
      'context' => $context,
    ], $mail_params->get('field_reply_to')->value);
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

<?php

namespace Drupal\social_user\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\social_queue_storage\Entity\QueueStorageEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Advanced Queue Job to process email to users.
 *
 * @AdvancedQueueJobType(
 *   id = "user_email_queue",
 *   label = @Translation("User email processor"),
 *   max_retries = 0,
 *   retry_delay = 0,
 * )
 */
class UserMailQueueJob extends JobTypeBase implements ContainerFactoryPluginInterface {

  use LoggerChannelTrait;

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
  public function process(Job $job) {
    try {
      // Get the Job data.
      $data = $job->getPayload();

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
          if (!empty($data['user_mail_addresses'])) {
            foreach ($data['user_mail_addresses'] as $mail_address) {
              if ($this->emailValidator->isValid($mail_address['email_address'])) {
                // Attempt sending mail.
                $this->sendMail($mail_address['email_address'], $this->languageManager->getDefaultLanguage()->getId(), $queue_storage, $mail_address['display_name']);
              }
            }
          }

          // Saving the entity and setting it to finished should send
          // a message about the batch completion.
          $queue_storage->setFinished(TRUE);
          $queue_storage->save();
          return JobResult::success('The user mail job was successfully finished.');
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
    $subject = $mail_params->get('field_subject')->getValue();
    $message = $mail_params->get('field_message')->getValue();
    $reply_to = $mail_params->get('field_reply_to')->getValue();
    $reply = NULL;

    if (!empty($reply_to)) {
      $reply = $reply_to[0]['value'];
    }

    $context = [
      'subject' => $subject[0]['value'],
      'message' => $message[0]['value'],
    ];

    if ($display_name) {
      $context['display_name'] = $display_name;
    }

    // Attempt sending mail.
    $this->mailManager->mail('system', 'action_send_email', $user_mail, $langcode, [
      'context' => $context,
    ], $reply);
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

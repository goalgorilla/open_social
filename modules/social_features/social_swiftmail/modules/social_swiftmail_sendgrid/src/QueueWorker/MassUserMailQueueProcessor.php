<?php

namespace Drupal\social_swiftmail_sendgrid\Plugin\QueueWorker;

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
 * Queue worker to process email to users by using the Sendgrid Substitions.
 *
 * @QueueWorker(
 *   id = "mass_user_email_queue",
 *   title = @Translation("Mass user email processor based on placeholders"),
 *   cron = {"time" = 60}
 * )
 */
class MassUserMailQueueProcessor extends QueueWorkerBase implements ContainerFactoryPluginInterface {

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MailManagerInterface $mail_manager, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation, LanguageManagerInterface $language_manager, EmailValidatorInterface $email_validator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mailManager = $mail_manager;
    $this->storage = $entity_type_manager;
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
      $valid_user_names = [];
      $valid_user_addresses = [];
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
            // Instead of sending the email, we add the email address to a list
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
        if ($data['user_mail_addresses']) {
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
          $this->sendMail($all_valid_emails, $this->languageManager->getDefaultLanguage()
            ->getId(), $queue_storage, $valid_user_names);
        }
      }
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
  protected function sendMail(string $valid_user_addresses, string $langcode, QueueStorageEntity $queue_storage, array $valid_user_names) {
    // Lets build the mail for SendGrid.
    $params = [
      'subject' => $queue_storage->get('field_subject')->value,
      'message' => $queue_storage->get('field_message')->value,
    ];

    // Add sendgrid specific data to the params.
    if (!empty($valid_user_names)) {
      // The key of the array is the needle in the haystack of the substitution.
      // also see social_swiftmail_sendgrid_token_alter.
      $params['sendgrid']['substitutions']['{{social_user:recipient}}'] = $valid_user_names;
    }

    // The from address.
    $from = $queue_storage->get('field_reply_to')->value;

    try {
      $this->mailManager->mail('social_swiftmail_sendgrid', 'action_send_email', $valid_user_addresses, $langcode, $params, $from);

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

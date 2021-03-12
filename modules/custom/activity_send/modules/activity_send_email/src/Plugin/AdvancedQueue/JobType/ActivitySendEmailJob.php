<?php

namespace Drupal\activity_send_email\Plugin\AdvancedQueue\JobType;

use Drupal\activity_creator\ActivityNotifications;
use Drupal\activity_send_email\EmailFrequencyManager;
use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\social_queue_storage\Entity\QueueStorageEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Advanced Queue Job to process email to users based on activities.
 *
 * @AdvancedQueueJobType(
 *   id = "activity_send_email_worker",
 *   label = @Translation("Activity email processor"),
 *   max_retries = 0,
 *   retry_delay = 0,
 * )
 */
class ActivitySendEmailJob extends JobTypeBase implements ContainerFactoryPluginInterface {

  /**
   * The email frequency manager.
   *
   * @var \Drupal\activity_send_email\EmailFrequencyManager
   */
  protected $frequencyManager;

  /**
   * Database services.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The activity notification service.
   *
   * @var \Drupal\activity_creator\ActivityNotifications
   */
  protected $activityNotifications;

  /**
   * Social mail settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $swiftmailSettings;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The queue service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EmailFrequencyManager $frequency_manager,
    Connection $connection,
    ActivityNotifications $activity_notifications,
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    QueueFactory $queue_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->frequencyManager = $frequency_manager;
    $this->database = $connection;
    $this->activityNotifications = $activity_notifications;
    $this->swiftmailSettings = $config_factory->get('social_swiftmail.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->queueFactory = $queue_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.emailfrequency'),
      $container->get('database'),
      $container->get('activity_creator.activity_notifications'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('queue')
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
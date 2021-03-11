<?php

namespace Drupal\activity_send_email\Plugin\QueueWorker;

use Drupal\activity_creator\ActivityNotifications;
use Drupal\activity_send\Plugin\QueueWorker\ActivitySendWorkerBase;
use Drupal\activity_send_email\EmailFrequencyManager;
use Drupal\activity_send_email\Plugin\ActivityDestination\EmailActivityDestination;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class ActivitySendEmailWorker extends ActivitySendWorkerBase implements ContainerFactoryPluginInterface {

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
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

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
    QueueFactory $queue_factory,
    LanguageManager $language_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->frequencyManager = $frequency_manager;
    $this->database = $connection;
    $this->activityNotifications = $activity_notifications;
    $this->swiftmailSettings = $config_factory->get('social_swiftmail.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->queueFactory = $queue_factory;
    $this->languageManager = $language_manager;
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
      $container->get('queue'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // First make sure it's an actual Activity entity.
    $activity_storage = $this->entityTypeManager->getStorage('activity');

    /** @var \Drupal\activity_creator\Entity\Activity $activity */
    if (!empty($data['entity_id']) && ($activity = $activity_storage->load($data['entity_id']))) {
      // Check if activity related entity exist.
      if (!($activity->getRelatedEntity() instanceof EntityInterface)) {
        $activity->delete();
        $this->activityNotifications->deleteNotificationsbyIds([$activity->id()]);
        return;
      }

      // Get Message Template id.
      $message_storage = $this->entityTypeManager->getStorage('message');
      /** @var \Drupal\message\Entity\Message $message */
      $message = $message_storage->load($activity->field_activity_message->target_id);
      $message_template_id = $message->getTemplate()->id();

      if (empty($data['recipients'])) {
        $recipients = array_column($activity->field_activity_recipient_user->getValue(), 'target_id');

        if (count($recipients) > 50) {
          // Split up by 50.
          $batches = array_chunk($recipients, 50);

          // Create items for this queue again for further processing.
          foreach ($batches as $key => $batch_recipients) {
            // Create same queue item, but with IDs of just 50 users.
            $batch_data = [
              'entity_id' => $data['entity_id'],
              'recipients' => $batch_recipients,
            ];

            $queue = $this->queueFactory->get('activity_send_email_worker');
            $queue->createItem($batch_data);
          }

          // We split up in batches. We can stop processing this specific queue
          // item.
          return;
        }
      }
      else {
        $recipients = $data['recipients'];
      }

      if (!empty($recipients)) {
        // Grab the platform default "Email notification frequencies".
        $template_frequencies = $this->swiftmailSettings->get('template_frequencies') ?: [];
        // Determine email frequency to use, defaults to immediately.
        $current_message_frequency = $template_frequencies[$message_template_id] ?? FREQUENCY_IMMEDIATELY;

        // Is the website multilingual.
        $is_multilingual = $this->languageManager->isMultilingual();

        // Prepare an array of all details required to process the item.
        $parameters = [
          'activity' => $activity,
          'message' => $message,
          'message_template_id' => $message_template_id,
          'current_message_frequency' => $current_message_frequency,
        ];

        // Get the settings for all users at once.
        $parameters['all_users_email_settings'] = EmailActivityDestination::getSendEmailAllUsersSetting($recipients, $parameters['message_template_id']);

        // We want to give preference to users who have set notification
        // settings as 'immediately'.
        $email_frequencies = [
          'immediately',
          'daily',
          'weekly',
        ];

        $user_storage = $this->entityTypeManager->getStorage('user');

        foreach ($email_frequencies as $email_frequency) {
          if ($target_recipients = EmailActivityDestination::getSendEmailUsersIdsByFrequency($recipients, $message_template_id, $email_frequency)) {
            if ($is_multilingual) {
              // We also want to send emails to users per language in a given
              // frequency.
              foreach ($languages = $this->languageManager->getLanguages() as $language) {
                $langcode = $language->getId();
                // Load all user by given language.
                $parameters['target_accounts'] = $user_storage->loadByProperties([
                  'preferred_langcode' => $langcode,
                  'uid' => $target_recipients,
                ]);
                // Get the message text according to language.
                $body_text = EmailActivityDestination::getSendEmailOutputText($parameters['message'], $langcode);
                $parameters['body_text'] = $body_text;
                // Send for further processing.
                $this->sendToFrequencyManager($parameters);
              }
            }
            else {
              // We load all the target accounts.
              $parameters['target_accounts'] = $user_storage->loadMultiple($target_recipients);
              $parameters['body_text'] = EmailActivityDestination::getSendEmailOutputText($message);
              $this->sendToFrequencyManager($parameters);
            }
          }
        }
      }
    }
  }

  /**
   * Send the queue items for further processing by frequency managers.
   *
   * @param array $parameters
   *   The array of message_tempalte_id, current_message_frequency,
   *   target_account, activity entity, email body text.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function sendToFrequencyManager(array $parameters) {
    if (!empty($parameters['target_accounts'])) {
      $current_message_frequency = $parameters['current_message_frequency'];
      /** @var \Drupal\user\Entity\User $target_account */
      foreach ($parameters['target_accounts'] as $target_account) {
        if (($target_account instanceof User) && !$target_account->isBlocked()) {
          // Only for users that have access to related content.
          if ($parameters['activity']->getRelatedEntity()->access('view', $target_account)) {
            // Retrieve the users email settings.
            if (!empty($parameters['all_users_email_settings'][$target_account->id()])) {
              $current_message_frequency = $parameters['all_users_email_settings'][$target_account->id()];
            }
            // Send item to EmailFrequency instance.
            $instance = $this->frequencyManager->createInstance($current_message_frequency);
            $instance->processItem($parameters['activity'], $parameters['body_text'], $target_account);
          }
        }
      }
    }
  }

}

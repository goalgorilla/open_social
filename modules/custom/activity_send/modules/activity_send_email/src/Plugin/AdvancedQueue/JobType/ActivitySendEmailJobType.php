<?php

namespace Drupal\activity_send_email\Plugin\AdvancedQueue\JobType;

use Drupal\activity_creator\ActivityNotifications;
use Drupal\activity_send_email\EmailFrequencyManager;
use Drupal\activity_send_email\Plugin\ActivityDestination\EmailActivityDestination;
use Drupal\advancedqueue\Entity\Queue;
use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Advanced Queue Job to process email workers and send them to SendGrid.
 *
 * @AdvancedQueueJobType(
 *   id = "activity_send_email_worker",
 *   label = @Translation("Process activity_send_email queue"),
 *   max_retries = 0,
 *   retry_delay = 0,
 * )
 */
class ActivitySendEmailJobType extends JobTypeBase implements ContainerFactoryPluginInterface {

  use LoggerChannelTrait;

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
  public function process(Job $job) {
    try {
      // Get the Job data.
      $data = $job->getPayload();

      // First make sure it's an actual Activity entity.
      $activity_storage = $this->entityTypeManager->getStorage('activity');

      if (!empty($data['entity_id']) && ($activity = $activity_storage->load($data['entity_id']))) {
        // Check if activity related entity exist.
        /** @var \Drupal\activity_creator\Entity\Activity $activity */
        if (!($activity->getRelatedEntity() instanceof EntityInterface)) {
          $activity->delete();
          $this->activityNotifications->deleteNotificationsbyIds([$activity->id()]);
          $this->getLogger('activity_send_email_worker')->notice('The activity was already deleted. We marked it as successful.');
          return JobResult::success('The activity was already deleted. We marked it as successful.');
        }

        // Is the website multilingual.
        $is_multilingual = $this->languageManager->isMultilingual();

        if (empty($data['recipients'])) {
          $recipients = array_column($activity->field_activity_recipient_user->getValue(), 'target_id');

          if (count($recipients) > 50) {
            if ($is_multilingual) {
              // We also want to send emails to users per language in a given
              // frequency.
              foreach ($languages = $this->languageManager->getLanguages() as $language) {
                $langcode = $language->getId();
                // Load all user by given language.
                $user_ids_per_language = $this->database->select('users_field_data', 'ufd')
                  ->fields('ufd', ['uid'])
                  ->condition('uid', $recipients, 'IN')
                  ->condition('preferred_langcode', $langcode)
                  ->execute()->fetchAllKeyed(0, 0);

                // Prepare the batch per language.
                $this->prepareBatch($data, $user_ids_per_language, $langcode);
              }
            }
            else {
              $this->prepareBatch($data, $recipients);
            }
            // We split up in batches. We can stop processing this specific queue
            // item.
            $this->getLogger('activity_send_email_worker')->notice('The Job was not finished correctly, no error thrown.');
            return JobResult::success('The Job was has been split up in batches.');
          }
        }
        else {
          $recipients = $data['recipients'];
        }

        if (!empty($recipients)) {
          // Get Message Template id.
          $message_storage = $this->entityTypeManager->getStorage('message');
          /** @var \Drupal\message\Entity\Message $message */
          $message = $message_storage->load($activity->getFieldValue('field_activity_message', 'target_id'));
          $message_template_id = $message->getTemplate()->id();

          // Prepare an array of all details required to process the item.
          $parameters = [
            'activity' => $activity,
            'message' => $message,
            'message_template_id' => $message_template_id,
          ];

          // We want to give preference to users who have set notification
          // settings as 'immediately'.
          $email_frequencies = [
            'immediately',
            'daily',
            'weekly',
            'none',
          ];

          // Let's store the users IDs which will be processed by the loop.
          $processed_users = [];
          foreach ($email_frequencies as $email_frequency) {
            // Get the 'target recipients' of who have their 'email notification
            // preference' matching to current $email_frequency.
            if ($target_recipients = EmailActivityDestination::getSendEmailUsersIdsByFrequency($recipients, $message_template_id, $email_frequency)) {
              // Update process users.
              $processed_users = array_merge($processed_users, $target_recipients);

              // We load all the target accounts.
              $parameters['target_recipients'] = $target_recipients;
              // We set the frequency of email.
              $parameters['frequency'] = $email_frequency;

              // If the batch has langcode.
              if (!empty($data['langcode'])) {
                $parameters['langcode'] = $data['langcode'];
              }

              // Send for further processing.
              $this->sendToFrequencyManager($parameters);
            }
          }

          // There is possibility where the users have not saved their
          // 'email notification preferences'. So, we check the difference
          // between the processed user IDs and original recipients users IDs
          // and send emails according to default 'frequency' set by site
          // manager. If SM has also not set, take 'immediately' as frequency.
          if ($remaining_users = array_diff($recipients, $processed_users)) {
            // Grab the platform default "Email notification frequencies".
            $template_frequencies = $this->swiftmailSettings->get('template_frequencies') ?: [];
            // Determine email frequency to use, defaults to immediately.
            $parameters['frequency'] = $template_frequencies[$message_template_id] ?? FREQUENCY_IMMEDIATELY;
            $parameters['target_recipients'] = $remaining_users;
            $this->sendToFrequencyManager($parameters);
          }
        }
      }

      // Mark the Job as successful.
      $this->getLogger('activity_send_email_worker')->notice('The job was finished correctly.');
      return JobResult::success('The job was finished correctly.');
    }
    catch (\Exception $e) {
      $this->getLogger('activity_send_email_worker')->error($e->getMessage());
      return JobResult::failure($e->getMessage());
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
    if (empty($parameters['target_recipients'])) {
      $this->getLogger('activity_send_email_worker')->error('We expected some recipients. None were provided.');
      return JobResult::failure('We expected some recipients. None were provided.');
    }

    $user_storage = $this->entityTypeManager->getStorage('user');
    if (!empty($parameters['langcode'])) {
      // Get the message text according to language.
      $body_text = EmailActivityDestination::getSendEmailOutputText($parameters['message'], $parameters['langcode']);
    }
    else {
      // We get the default body text.
      $body_text = EmailActivityDestination::getSendEmailOutputText($parameters['message']);
    }

    // We load all the target accounts.
    $target_accounts = $user_storage->loadMultiple($parameters['target_recipients']);
    if (!empty($target_accounts)) {
      /** @var \Drupal\user\Entity\User $target_account */
      foreach ($target_accounts as $target_account) {
        if (($target_account instanceof User) && !$target_account->isBlocked()) {
          // Only for users that have access to related content.
          if ($parameters['activity']->getRelatedEntity()->access('view', $target_account)) {
            // If the website is multilingual, get the body text in
            // users preferred language. This will happen when the queue item
            // is not processed in a batch and thus we can't be sure if all
            // users in the queue have the same language.
            if (empty($parameters['langcode']) && $this->languageManager->isMultilingual()) {
              $body_text = EmailActivityDestination::getSendEmailOutputText(
                $parameters['message'],
                $target_account->getPreferredLangcode()
              );
            }
            // Send item to EmailFrequency instance.
            $instance = $this->frequencyManager->createInstance($parameters['frequency']);
            $instance->processItem($parameters['activity'], $parameters['message'], $target_account, $body_text);
          }
        }
      }
      return JobResult::success('We have successfully scheduled items to be processed by the Frequency Manager.');
    }
  }

  /**
   * Prepares the batch processing for this queue item.
   *
   * @param array $data
   *   Array of batch data.
   * @param array $user_ids_per_language
   *   Array of user IDs.
   * @param string|null $langcode
   *   Language code.
   */
  private function prepareBatch(array $data, array $user_ids_per_language, $langcode = NULL) {
    // Split up by 50.
    $batches = array_chunk($user_ids_per_language, 50);

    // Create items for this queue again for further processing.
    foreach ($batches as $key => $batch_recipients) {
      // Create same queue item, but with IDs of just 50 users.
      $batch_data = [
        'entity_id' => $data['entity_id'],
        'recipients' => $batch_recipients,
        'langcode' => $langcode,
      ];

      // Instead of creating a Queue item we use Advanced Queue.
      $job = Job::create('activity_send_email_worker', $batch_data);
      if ($job instanceof Job) {
        $queue = Queue::load('default');
        $queue->enqueueJob($job);
      }
    }
  }

}

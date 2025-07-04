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
use Drupal\group\Entity\GroupInterface;
use Drupal\social_group\GroupMuteNotify;
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
  protected $socialMailerSettings;

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
   * The group mute notifications.
   *
   * @var \Drupal\social_group\GroupMuteNotify
   */
  protected $groupMuteNotify;

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
    LanguageManager $language_manager,
    GroupMuteNotify $group_mute_notify,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->frequencyManager = $frequency_manager;
    $this->database = $connection;
    $this->activityNotifications = $activity_notifications;
    $this->socialMailerSettings = $config_factory->get('social_swiftmail.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->queueFactory = $queue_factory;
    $this->languageManager = $language_manager;
    $this->groupMuteNotify = $group_mute_notify;
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
      $container->get('language_manager'),
      $container->get('social_group.group_mute_notify')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // First make sure it's an actual Activity entity.
    $activity_storage = $this->entityTypeManager->getStorage('activity');

    if (!empty($data['entity_id']) && ($activity = $activity_storage->load($data['entity_id']))) {
      // Check if activity related entity exist.
      /** @var \Drupal\activity_creator\Entity\Activity $activity */
      if (!($activity->getRelatedEntity() instanceof EntityInterface)) {
        $activity->delete();
        $this->activityNotifications->deleteNotificationsbyIds([$activity->id()]);
        return;
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
          return;
        }
      }
      else {
        $recipients = $data['recipients'];
      }

      if (!empty($recipients)) {
        // Get Message Template id.
        $message_storage = $this->entityTypeManager->getStorage('message');
        /** @var \Drupal\message\Entity\Message $message */
        $message = $message_storage->load($activity->field_activity_message->target_id);
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
          $template_frequencies = $this->socialMailerSettings->get('template_frequencies') ?: [];
          // Determine email frequency to use, defaults to immediately.
          $parameters['frequency'] = $template_frequencies[$message_template_id] ?? FREQUENCY_IMMEDIATELY;
          $parameters['target_recipients'] = $remaining_users;
          $this->sendToFrequencyManager($parameters);
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
    if (empty($parameters['target_recipients'])) {
      return;
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

    // Get the related entity from the activity.
    $related_entity = $parameters['activity']->getRelatedEntity();

    // We load all the target accounts.
    if ($target_accounts = $user_storage->loadMultiple($parameters['target_recipients'])) {
      /** @var \Drupal\group\Entity\GroupInterface $group */
      $group = $this->groupMuteNotify->getGroupByContent($related_entity);

      /** @var \Drupal\user\Entity\User $target_account */
      foreach ($target_accounts as $target_account) {
        // Filter out blocked users early in the process.
        if (($target_account instanceof User) && !$target_account->isBlocked()) {
          // If a site manager decides emails should not be sent to users
          // who have never logged in. We need to verify last accessed time,
          // so those users are not processed.
          if ($this->socialMailerSettings->get('do_not_send_emails_new_users') && (int) $target_account->getLastAccessedTime() === 0) {
            continue;
          }
          // Check if we have $group set which means that this content was
          // posted in a group.
          if ($group instanceof GroupInterface) {
            // Skip the notification for users which have muted the group
            // notification in which this content was posted.
            if ($this->groupMuteNotify->groupNotifyIsMuted($group, $target_account)) {
              continue;
            }
          }

          // Only for users that have access to related content.
          if ($related_entity->access('view', $target_account)) {
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

            if ($this->frequencyManager->hasDefinition($parameters['frequency'])) {
              // Send item to EmailFrequency instance.
              $instance = $this->frequencyManager->createInstance($parameters['frequency']);
              $instance->processItem($parameters['activity'], $parameters['message'], $target_account, $body_text);
            }
          }
        }
      }
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

      $queue = $this->queueFactory->get('activity_send_email_worker');
      $queue->createItem($batch_data);
    }
  }

}

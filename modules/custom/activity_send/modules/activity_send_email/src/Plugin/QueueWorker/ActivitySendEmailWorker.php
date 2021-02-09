<?php

namespace Drupal\activity_send_email\Plugin\QueueWorker;

use Drupal\activity_creator\ActivityNotifications;
use Drupal\activity_creator\Entity\Activity;
use Drupal\activity_send\Plugin\QueueWorker\ActivitySendWorkerBase;
use Drupal\activity_send_email\EmailFrequencyManager;
use Drupal\activity_send_email\Plugin\ActivityDestination\EmailActivityDestination;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\message\Entity\Message;
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
  public function processItem($data) {
    // First make sure it's an actual Activity entity.
    $activity_storage = $this->entityTypeManager->getStorage('activity');
    $activity = $activity_storage->load($data['entity_id']);
    if (!empty($data['entity_id']) && !is_null($activity)) {
      // Check if activity related entity exist.
      if (!$activity->getRelatedEntity()) {
        $activity->delete();
        $this->activityNotifications->deleteNotificationsbyIds([$activity->id()]);
        return;
      }

      // Get Message Template id.
      $message_storage = $this->entityTypeManager->getStorage('message');
      $message = $message_storage->load($activity->field_activity_message->target_id);
      $message_template_id = $message->getTemplate()->id();

      // Get target account.
      $user_storage = $this->entityTypeManager->getStorage('user');
      if (empty($data['recipients'])) {
        $recipients = array_column($activity->field_activity_recipient_user->getValue(), 'target_id');

        if (count($recipients) > 50) {
          // Split up by 50.
          $batches = array_chunk($recipients, 50);

          foreach ($batches as $key => $batch_recipients) {
            // Only for users that have access to related content.
            foreach ($batch_recipients as $key => $batch_recipient) {
              $recipient = $user_storage->load($batch_recipient);
              if (
                !is_null($activity->getRelatedEntity()) &&
                !$activity->getRelatedEntity()->access('view', $recipient)
              ) {
                unset($batch_recipients[$key]);
              }
            }
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

      // Load the user accounts.
      $target_accounts = $user_storage->loadMultiple($recipients);

      foreach ($target_accounts as $target_account) {
        if (!is_null($target_account)) {
          // Retrieve the users email settings.
          $user_email_settings = EmailActivityDestination::getSendEmailUserSettings($target_account);

          // Determine email frequency to use, defaults to immediately.
          // @todo make these frequency constants?
          $template_frequencies = $this->swiftmailSettings->get('template_frequencies') ?: [];
          $frequency = isset($template_frequencies[$message_template_id]) ? $template_frequencies[$message_template_id] : 'immediately';
          if (!empty($user_email_settings[$message_template_id])) {
            $frequency = $user_email_settings[$message_template_id];
          }

          // Send item to EmailFrequency instance.
          $instance = $this->frequencyManager->createInstance($frequency);
          $instance->processItem($activity, $message, $target_account);
        }
      }
    }
  }

}

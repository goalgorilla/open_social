<?php

namespace Drupal\activity_send_email\Plugin\QueueWorker;

use Drupal\activity_creator\ActivityNotifications;
use Drupal\activity_creator\Entity\Activity;
use Drupal\activity_send\Plugin\QueueWorker\ActivitySendWorkerBase;
use Drupal\activity_send_email\EmailFrequencyManager;
use Drupal\activity_send_email\Plugin\ActivityDestination\EmailActivityDestination;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EmailFrequencyManager $frequency_manager,
    Connection $connection,
    ActivityNotifications $activity_notifications
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->frequencyManager = $frequency_manager;
    $this->database = $connection;
    $this->activityNotifications = $activity_notifications;
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
      $container->get('activity_creator.activity_notifications')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // First make sure it's an actual Activity entity.
    if (!empty($data['entity_id']) && $activity = Activity::load($data['entity_id'])) {
      // Check if activity related entity exist.
      if (!$activity->getRelatedEntity()) {
        $activity->delete();
        $this->activityNotifications->deleteNotificationsbyIds([$activity->id()]);
        return;
      }

      // Get Message Template id.
      $message = Message::load($activity->field_activity_message->target_id);
      $message_template_id = $message->getTemplate()->id();

      // Get target account.
      if (empty($data['recipients'])) {
        $recipients = array_column($activity->field_activity_recipient_user->getValue(), 'target_id');

        if (count($recipients) > 50) {
          // Split up by 50.
          $batches = array_chunk($recipients, 50);

          foreach ($batches as $batch_recipients) {
            // Create same queue item, but with IDs of just 50 users.
            $batch_data = [
              'entity_id' => $data['entity_id'],
              'recipients' => $batch_recipients,
            ];

            $queue = \Drupal::queue('activity_send_email_worker');
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
      $target_accounts = User::loadMultiple($recipients);

      foreach ($target_accounts as $target_account) {
        if ($target_account instanceof User) {

          // Retrieve the users email settings.
          $user_email_settings = EmailActivityDestination::getSendEmailUserSettings($target_account);

          // Determine email frequency to use, defaults to immediately.
          // @todo make these frequency constants?
          $frequency = 'immediately';
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

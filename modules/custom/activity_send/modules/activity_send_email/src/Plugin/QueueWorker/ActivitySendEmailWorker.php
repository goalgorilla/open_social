<?php

namespace Drupal\activity_send_email\Plugin\QueueWorker;

use Drupal\activity_send_email\EmailFrequencyManager;
use Drupal\activity_send_email\Plugin\ActivityDestination\EmailActivityDestination;
use Drupal\activity_send\Plugin\QueueWorker\ActivitySendWorkerBase;
use Drupal\activity_creator\Entity\Activity;
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
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EmailFrequencyManager $frequency_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->frequencyManager = $frequency_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.emailfrequency')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // First make sure it's an actual Activity entity.
    if (!empty($data['entity_id']) && $activity = Activity::load($data['entity_id'])) {
      // Get Message Template id.
      $message = Message::load($activity->field_activity_message->target_id);
      $message_template_id = $message->getTemplate()->id();

      // Get target account.
      $target_accounts = EmailActivityDestination::getSendTargetUsers($activity);
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

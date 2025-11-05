<?php

namespace Drupal\activity_send_push\Plugin\ActivitySend;

use Drupal\activity_send\Plugin\ActivitySendBase;
use Drupal\activity_creator\ActivityInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\message\Entity\Message;
use Drupal\social_pwa\WebPushManagerInterface;
use Drupal\social_pwa\WebPushPayload;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Minishlink\WebPush\WebPush;
use Drupal\file\Entity\File;
use Psr\Log\LoggerInterface;

/**
 * Provides a 'PushActivitySend' activity action.
 *
 * @ActivitySend(
 *  id = "push_activity_send",
 *  label = @Translation("Action that is triggered when a entity is created"),
 * )
 */
class PushActivitySend extends ActivitySendBase implements ContainerFactoryPluginInterface {

  /**
   * The web push manager.
   */
  protected WebPushManagerInterface $webPushManager;

  /**
   * The social PWA settings.
   */
  protected ImmutableConfig $settings;

  /**
   * The logger for this module.
   */
  protected LoggerInterface $logger;

  /**
   * Database services.
   */
  protected Connection $database;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WebPushManagerInterface $web_push_manager, ImmutableConfig $settings, LoggerInterface $logger, Connection $connection, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->webPushManager = $web_push_manager;
    $this->settings = $settings;
    $this->logger = $logger;
    $this->database = $connection;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   *
   * Arguments have been adjusted for backwards compatibility with Open Social.
   */
  public static function create($container, array $configuration = [], $plugin_id = NULL, $plugin_definition = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('social_pwa.web_push_manager'),
      $container->get('config.factory')->get('social_pwa.settings'),
      $container->get('logger.factory')->get('activity_send_push'),
      $container->get('database'),
      $container->get('module_handler'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process(ActivityInterface $activity): void {
    // If push is disabled then we don't do anything.
    $push_enabled = $this->settings->get('status.all');
    if (!$push_enabled) {
      return;
    }

    $user_ids = array_column($activity->field_activity_recipient_user->getValue(), 'target_id');

    // If there is no recipient user for this activity then we can't send a push
    // notification to anyone.
    if (empty($user_ids)) {
      // We log this as a debug message since it doesn't make sense to use push
      // notifications for things that don't affect users. Detecting these
      // scenarios helps us improve the notification system.
      $this->logger->debug('Tried to send push notification for activity from message (%mid) that did not have a user recipient.', ['%mid' => $activity->field_activity_message->target_id]);
      return;
    }

    // When we are loading users from a group we can already filter out
    // the active subscriptions to easy the load later on.
    $user_ids = $this->database->select('users_data', 'ud')
      ->fields('ud', ['uid'])
      ->condition('module', 'social_pwa')
      ->condition('name', 'subscription')
      ->condition('value', 'a:0:{}', '<>')
      ->condition('value', '', '<>')
      ->condition('uid', $user_ids, 'IN')
      ->execute()->fetchAllKeyed(0, 0);

    foreach ($user_ids as $user_id) {
      $user = User::load($user_id);
      if (!$user instanceof UserInterface) {
        $this->logger->debug('Ignored push notification for non-existing user.');
        return;
      }

      $user_subscriptions = $this->webPushManager->getSubscriptionsForUser($user);

      // If the user has no push subscriptions then we can stop early.
      if (empty($user_subscriptions)) {
        continue;
      }

      // Prepare the payload with the message.
      $message_loaded = Message::load($activity->field_activity_message->target_id);
      $message = $message_loaded->getText();
      if (empty($message[0])) {
        $this->logger->error('Tried to send an empty push notification for mid: %mid', ['%mid' => $activity->field_activity_message->target_id]);
        continue;
      }
      $message_to_send = $message[0];

      $url = $activity->getRelatedEntityUrl();

      // Alter URL.
      $this->moduleHandler->alter('activity_send_push_url', $url, $activity, $message_loaded);

      // If the related entity does not have a canonical URL then we don't have
      // anywhere for the user to go to when they click the push notification so
      // we shouldn't send a notification at all.
      if (!($url instanceof Url)) {
        $this->logger->error("Tried to send push notification for mid: %mid but the target entity doesn't have a canonical url", ['%mid' => $activity->field_activity_message->target_id]);
        continue;
      }

      // Set fields for payload.
      $message_to_send = html_entity_decode($message_to_send);
      $push_data = [
        'message' => strip_tags($message_to_send),
        'site_name' => $this->settings->get('name'),
        'url' => $url->toString(),
      ];

      $icon = $this->settings->get('icons.icon');
      if (!empty($icon)) {
        // Get the file id and path.
        $fid = $icon[0];
        /** @var \Drupal\file\Entity\File $file */
        $file = File::load($fid);
        $path = $file->createFileUrl(FALSE);

        $push_data['icon'] = \Drupal::service('file_url_generator')
          ->transformRelative($path);
      }

      // Encode payload.
      $serialized_payload = (new WebPushPayload('legacy', $push_data))->toJson();

      $auth = $this->webPushManager->getAuth();
      $webPush = new WebPush($auth);

      foreach ($user_subscriptions as $subscription) {
        $webPush->queueNotification($subscription, $serialized_payload);
      }

      $outdated_subscriptions = [];
      // Send each notification and check the results.
      // flush() returns a generator that won't actually send all batches until
      // we've consumed all the results of the previous batch.
      /** @var \Minishlink\WebPush\MessageSentReport $push_result */
      foreach ($webPush->flush() as $push_result) {
        if ($push_result->isSubscriptionExpired()) {
          $outdated_subscriptions[] = $push_result->getEndpoint();
        }
      }

      if (!empty($outdated_subscriptions)) {
        $this->webPushManager->removeSubscriptionsForUser($user, $outdated_subscriptions);
      }
    }
  }

}

<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\QueueWorker\ActivityWorkerLogger
 */

namespace Drupal\activity_creator\Plugin\QueueWorker;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * A report worker.
 *
 * @QueueWorker(
 *   id = "activity_creator_logger",
 *   title = @Translation("Process activity loggers."),
 *   cron = {"time" = 60}
 * )
 *
 * This QueueWorker is responsible for processing ActivityLog items and will
 * retrieve recipients batched and then create new queue items for processing in
 * QueueWorker ActivityWorkerActivities.
 */
class ActivityWorkerLogger extends ActivityWorkerBase {

  /**
   * The ActivityContext manager
   *
   * @var \Drupal\activity_creator\Plugin\ActivityContextManager
   */
  protected $context_plugin_manager;

  /**
   * The state.
   *
   * @var \Drupal\Core\State\
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {

    $old_data = $data;

    // Get 100 Recipients at a time.
    $limit = 0;

    // TODO: Change this to use dependency injection (see construct code below)
    $context_plugin_manager = \Drupal::service('plugin.manager.activity_context.processor');

    /** @var $plugin \Drupal\activity_creator\Plugin\ActivityContextBase */
    // @TODO Do we need multiple context plugins? If so should we call Manager?
    $plugin = $context_plugin_manager->createInstance($data['context']);
    $recipients = $plugin->getRecipients($data, $data['last_uid'], $limit);

    if (!empty($recipients)) {

      foreach ($recipients as $recipient) {
        // Create a queue item for activity creation.
        $activity_creator_data = [
          'mid' => $data['mid'],
          'message_type' => $data['message_type'],
          'actor' => $data['actor'],
          'context' => $data['context'], // Not necessary?
          'destination' => $data['destination'],
          'related_object' => $data['related_object'],
          'recipient' => $recipient,
        ];
        $this->createQueueItem('activity_creator_activities', $activity_creator_data);

        $last_uid = $recipient;
      }

      // Now create new queue item for activity_creator_logger if necessary.
      if ($limit != 0 && count($recipients) >= $limit && isset($last_uid)) {
        $data['last_uid'] = $last_uid;
        $data['status'] = 'processing';
        $this->createQueueItem('activity_creator_logger', $data);
      }

    }
    else {

      $activity_creator_data = [
        'mid' => $data['mid'],
        'message_type' => $data['message_type'],
        'actor' => $data['actor'],
        'context' => $data['context'], // Not necessary?
        'destination' => $data['destination'],
        'related_object' => $data['related_object'],
      ];
      $this->createQueueItem('activity_creator_activities', $activity_creator_data);
    }

  }


//  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterfacee $state, LoggerChannelFactoryInterface $logger, $context_plugin_manager) {
//    parent::__construct($configuration, $plugin_id, $plugin_definition, $state, $logger);
//    $this->context_plugin_manager = $context_plugin_manager;
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
//    return new static(
//      $configuration,
//      $plugin_id,
//      $plugin_definition,
//      $container->get('state'),
//      $container->get('logger.factory'),
//      $container->get('plugin.manager.activity_context.processor')
//    );
//  }

}

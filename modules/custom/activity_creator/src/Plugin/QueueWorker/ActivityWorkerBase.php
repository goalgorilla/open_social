<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Plugin\QueueWorker\ActivityWorkerBase.
 */

namespace Drupal\activity_creator\Plugin\QueueWorker;

use Drupal\Core\State\StateInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Provides base functionality for the ReportWorkers.
 */
abstract class ActivityWorkerBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;


  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * ActivityWorkerBase constructor.
   *
   * @param array $configuration
   *   The configuration of the instance.
   * @param $plugin_id
   *   The plugin id.
   * @param $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service the instance should use.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger service the instance should use.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state, LoggerChannelFactoryInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state'),
      $container->get('logger.factory')
    );
  }

  /**
   * Simple reporter log and display information about the queue.
   *
   * @param int $worker
   *   Worker number.
   * @param object $item
   *   The $item which was stored in the cron queue.
   */
  protected function reportWork($worker, $item) {
    if ($this->state->get('cron_example_show_status_message')) {
      drupal_set_message(
        $this->t('Queue @worker worker processed item with sequence @sequence created at @time', [
          '@worker' => $worker,
          '@sequence' => $item->sequence,
          '@time' => date_iso8601($item->created),
        ])
      );
    }
    $this->logger->get('activity_creator')->info('Queue @worker worker processed item with sequence @sequence created at @time', [
      '@worker' => $worker,
      '@sequence' => $item->sequence,
      '@time' => date_iso8601($item->created),
    ]);
  }

  /**
   * Simple reporter log and display information about the queue.
   *
   * @param string $queue_name
   *   The queue name.
   * @param object $data
   *   The $data which should be stored in the queue item.
   */
  protected function createQueueItem($queue_name, $data) {
    $queue = \Drupal::queue($queue_name); // @TODO Use dependency injection here
    $queue->createItem($data);
  }




}

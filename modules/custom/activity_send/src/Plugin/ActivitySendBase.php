<?php

namespace Drupal\activity_send\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Queue\QueueFactory;

/**
 * Base class for Activity send plugins.
 */
abstract class ActivitySendBase extends PluginBase implements ActivitySendInterface {

  /**
   * The queue.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QueueFactory $queue) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->queue = $queue;
  }

}

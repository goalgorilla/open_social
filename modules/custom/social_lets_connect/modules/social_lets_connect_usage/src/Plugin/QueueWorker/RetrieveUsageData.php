<?php

namespace Drupal\social_lets_connect_usage\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\social_lets_connect_usage\Plugin\ShareUsageDataPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * A report worker.
 *
 * @QueueWorker(
 *   id = "retrieve_usage_data",
 *   title = @Translation("Retrieve usage data."),
 *   cron = {"time" = 120}
 * )
 *
 * This QueueWorker is responsible for retrieving usage data.
 */
class RetrieveUsageData extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Share Usage Data plugin manager.
   *
   * @var \Drupal\social_lets_connect_usage\Plugin\ShareUsageDataPluginManager
   */
  private $usageDataPluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ShareUsageDataPluginManager $usageDataPluginManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->usageDataPluginManager = $usageDataPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.share_usage_data_plugin')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // Let the plugin do the work.
    $instance = $this->usageDataPluginManager->createInstance($data['plugin_definition']['id']);
    $value = $instance->getValue();

    // TODO Save it in the state, keyvalue or create a new queue item.
    return $value;
  }

}

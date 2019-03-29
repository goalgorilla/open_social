<?php

namespace Drupal\social_lets_connect_usage\Plugin\QueueWorker;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Config\ConfigFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Send usage data.
 *
 * @QueueWorker(
 *   id = "send_usage_data",
 *   title = @Translation("Send usage data."),
 *   cron = {"time" = 300}
 * )
 *
 * This QueueWorker is responsible for sending usage data.
 */
class SendUsageData extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  public $config;

  /**
   * The http client.
   *
   * @var \GuzzleHttp\Client
   */
  public $client;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactory $config, Client $client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->config = $config;
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('http_client')
    );
  }

  /**
   * Will process a queue item.
   *
   *   $send_data['site_key'] = $site_key;
   *   $send_data['send_info'] = [
   *     'last_send' => $last_send,
   *     'times_send' => $times_send,
   *     'current_time' => $current_time,
   *   ];
   *   $send_data['usage_data'] = array $usage_data;
   */
  public function processItem($data) {
    $config = $this->config->get('social_lets_connect_usage.settings');
    $usage_data_url = $config->get('url');

    try {
      $response = $this->client->post($usage_data_url, ['json' => $data]);
    }
    catch (RequestException $e) {
      return FALSE;
    }
    return Json::decode($response->getBody()->getContents());
  }

}

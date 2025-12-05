<?php

declare(strict_types=1);

namespace Drupal\social_eda\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\social_eda\Plugin\BackfillHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Advanced Queue Job Type for processing backfill jobs.
 *
 * @AdvancedQueueJobType(
 *   id = "social_eda_backfill",
 *   label = @Translation("Social EDA Backfill"),
 *   max_retries = 3,
 *   retry_delay = 60,
 * )
 */
final class BackfillJobType extends JobTypeBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a BackfillJobType object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $backfillHandlerManager
   *   The backfill handler manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected PluginManagerInterface $backfillHandlerManager,
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');
    /** @var \Drupal\Component\Plugin\PluginManagerInterface $backfill_handler_manager */
    $backfill_handler_manager = $container->get('plugin.manager.social_eda.backfill_handler');
    /** @var \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory */
    $logger_factory = $container->get('logger.factory');

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_type_manager,
      $backfill_handler_manager,
      $logger_factory
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process(Job $job): JobResult {
    $payload = $job->getPayload();
    $logger = $this->loggerFactory->get('social_eda');

    // Validate payload structure.
    if (!isset($payload['plugin_id']) || !isset($payload['entity_type']) || !isset($payload['entity_id'])) {
      $message = sprintf('Invalid backfill job payload. Missing required fields: plugin_id=%s, entity_type=%s, entity_id=%s', $payload['plugin_id'] ?? 'missing', $payload['entity_type'] ?? 'missing', $payload['entity_id'] ?? 'missing');
      $logger->error($message);
      return JobResult::failure($message);
    }

    $plugin_id = $payload['plugin_id'];
    $entity_type = $payload['entity_type'];
    $entity_id = $payload['entity_id'];

    try {
      // Load the entity.
      $storage = $this->entityTypeManager->getStorage($entity_type);
      $entity = $storage->load($entity_id);

      if (!$entity instanceof EntityInterface) {
        $message = sprintf('Entity not found: %s:%s', $entity_type, $entity_id);
        $logger->warning($message);
        return JobResult::success($message);
      }

      // Get the backfill handler plugin.
      $handler = $this->backfillHandlerManager->createInstance($plugin_id);
      if (!$handler instanceof BackfillHandlerInterface) {
        $actual_class = get_class($handler);
        throw new \RuntimeException(sprintf('BackfillHandlerManager returned an invalid handler for plugin_id "%s". Expected instance of %s, got %s.', $plugin_id, BackfillHandlerInterface::class, $actual_class));
      }

      // Process the entity.
      $handler->process($entity);

      $message = sprintf('Successfully processed backfill for %s:%s using plugin %s', $entity_type, $entity_id, $plugin_id);
      $logger->info($message);
      return JobResult::success($message);
    }
    catch (\Exception $e) {
      $message = sprintf('Failed to process backfill job: %s', $e->getMessage());
      $logger->error($message, [
        'plugin_id' => $plugin_id,
        'entity_type' => $entity_type,
        'entity_id' => $entity_id,
        'exception' => $e,
      ]);
      return JobResult::failure($message);
    }
  }

}

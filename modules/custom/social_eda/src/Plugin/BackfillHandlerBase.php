<?php

declare(strict_types=1);

namespace Drupal\social_eda\Plugin;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Base class for BackfillHandler plugins.
 */
abstract class BackfillHandlerBase extends PluginBase implements BackfillHandlerInterface, ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity field manager.
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * The container.
   */
  protected ContainerInterface $container;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    ContainerInterface $container,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container
    );
  }

  /**
   * Builds the entity query with all conditions.
   *
   * Subclasses can override this method to add custom query conditions
   * or modify the query building logic.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $bundle
   *   The bundle name.
   * @param int|null $from
   *   Unix timestamp - entities created on or after this time.
   * @param int|null $to
   *   Unix timestamp - entities created on or before this time.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The configured entity query object.
   */
  protected function getQuery(string $entity_type, string $bundle, ?int $from = NULL, ?int $to = NULL): QueryInterface {
    $storage = $this->entityTypeManager->getStorage($entity_type);
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $storage->getQuery();
    $query->accessCheck(FALSE);

    // Add bundle condition if bundle is provided and entity supports bundles.
    if (!empty($bundle)) {
      $entity_type_definition = $this->entityTypeManager->getDefinition($entity_type);
      if ($entity_type_definition->hasKey('bundle')) {
        $bundle_key = $entity_type_definition->getKey('bundle');
        $query->condition($bundle_key, $bundle);
      }
    }

    // Check if date range filters are requested and entity type supports
    // a timestamp field ('created' or 'timestamp').
    if ($from !== NULL || $to !== NULL) {
      $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type);

      // Try 'created' first, then fall back to 'timestamp' if 'created'
      // doesn't exist.
      $date_field = NULL;
      if (isset($field_storage_definitions['created'])) {
        $date_field = 'created';
      }
      elseif (isset($field_storage_definitions['timestamp'])) {
        $date_field = 'timestamp';
      }

      if ($date_field === NULL) {
        throw new \RuntimeException(sprintf(
          'Entity type "%s" does not have a "created" or "timestamp" field. Date range filtering requires entities with a timestamp field.',
          $entity_type
        ));
      }

      if ($from !== NULL) {
        $query->condition($date_field, $from, '>=');
      }
      if ($to !== NULL) {
        $query->condition($date_field, $to, '<=');
      }
    }

    return $query;
  }

  /**
   * Validates that the plugin definition is an array.
   *
   * @throws \RuntimeException
   *   If the plugin definition is not an array.
   */
  protected function validatePluginDefinitionIsArray(): void {
    if (!is_array($this->pluginDefinition)) {
      throw new \RuntimeException(sprintf(
        'Plugin definition must be an array for plugin "%s".',
        $this->getPluginId()
      ));
    }
  }

  /**
   * Gets plugin context for error messages.
   *
   * @return array
   *   Array with keys: plugin_id, plugin_class, plugin_file, plugin_line.
   */
  private function getPluginContext(): array {
    $reflection = new \ReflectionClass($this);
    return [
      'plugin_id' => $this->getPluginId(),
      'plugin_class' => get_class($this),
      'plugin_file' => $reflection->getFileName(),
      'plugin_line' => $reflection->getStartLine(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityIds(?int $from = NULL, ?int $to = NULL): array {
    $this->validatePluginDefinitionIsArray();
    assert(is_array($this->pluginDefinition), 'Plugin definition must be an array.');

    if (!isset($this->pluginDefinition['entity_type'])) {
      throw new \RuntimeException(sprintf(
        'Plugin definition must contain "entity_type" key for plugin "%s".',
        $this->getPluginId()
      ));
    }

    $entity_type = $this->pluginDefinition['entity_type'];
    $bundle = $this->pluginDefinition['bundle'] ?? '';

    $query = $this->getQuery($entity_type, $bundle, $from, $to);

    $result = $query->execute();
    if (!is_array($result)) {
      throw new \RuntimeException(sprintf(
        'Entity query execute() must return an array for entity type "%s", got %s.',
        $entity_type,
        gettype($result)
      ));
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function process(EntityInterface $entity): void {
    $this->validatePluginDefinitionIsArray();
    // PHPStan type narrowing: validatePluginDefinitionIsArray() throws if not
    // array, but PHPStan doesn't track that flow, so we assert for static
    // analysis.
    assert(is_array($this->pluginDefinition), 'Plugin definition must be an array.');

    if (!isset($this->pluginDefinition['handler_service'])) {
      throw new \RuntimeException(sprintf(
        'Plugin definition must contain "handler_service" key for plugin "%s".',
        $this->getPluginId()
      ));
    }

    if (!isset($this->pluginDefinition['handler_method'])) {
      throw new \RuntimeException(sprintf(
        'Plugin definition must contain "handler_method" key for plugin "%s".',
        $this->getPluginId()
      ));
    }

    $handler_service = $this->pluginDefinition['handler_service'];
    $handler_method = $this->pluginDefinition['handler_method'];

    try {
      $handler = $this->container->get($handler_service);
    }
    catch (ServiceNotFoundException $e) {
      $context = $this->getPluginContext();
      $message = sprintf(
        'Handler service "%s" not found for plugin "%s" (class: %s, file: %s, line: %d).',
        $handler_service,
        $context['plugin_id'],
        $context['plugin_class'],
        $context['plugin_file'],
        $context['plugin_line']
      );
      throw new \RuntimeException($message, 0, $e);
    }
    catch (\Throwable $e) {
      // Catch any other container-related exceptions.
      $context = $this->getPluginContext();
      $message = sprintf(
        'Container error retrieving handler service "%s" for plugin "%s" (class: %s, file: %s, line: %d): %s',
        $handler_service,
        $context['plugin_id'],
        $context['plugin_class'],
        $context['plugin_file'],
        $context['plugin_line'],
        $e->getMessage()
      );
      throw new \RuntimeException($message, 0, $e);
    }

    // Check if the method exists and is callable.
    if (!is_callable([$handler, $handler_method])) {
      $handler_class = get_class($handler);
      throw new \RuntimeException(sprintf(
        'Handler service "%s" (class: %s) does not have callable method "%s"',
        $handler_service,
        $handler_class,
        $handler_method
      ));
    }

    $handler->{$handler_method}($entity);
  }

}

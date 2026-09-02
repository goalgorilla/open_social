<?php

declare(strict_types=1);

namespace Drupal\social_group\Plugin\GraphQL\SchemaExtension;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\social_graphql\Plugin\GraphQL\SchemaExtension\SchemaExtensionPluginBase;
use Drupal\social_group\GraphQL\HubTypeResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds group related variables.
 *
 * @SchemaExtension(
 *   id = "social_group_schema_extension",
 *   name = "Social Group Schema Extension",
 *   description = "Schema extension for group.",
 *   schema = "open_social"
 * )
 */
class GroupSchemaExtension extends SchemaExtensionPluginBase {

  /**
   * Constructs a GroupSchemaExtension.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param array $pluginDefinition
   *   The plugin definition array.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\social_group\GraphQL\HubTypeResolver $hubTypeResolver
   *   Resolves Hub to a concrete GraphQL type.
   */
  public function __construct(
    array $configuration,
    $pluginId,
    array $pluginDefinition,
    ModuleHandlerInterface $moduleHandler,
    protected HubTypeResolver $hubTypeResolver,
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition, $moduleHandler);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('social_group.hub_type_resolver'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    $registry->addTypeResolver('Hub', $this->hubTypeResolver);
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensionDefinition(): NULL {
    // Skipping social_group_schema_extension.extension.graphqls, as we have
    // nothing to write there.
    return NULL;
  }

}

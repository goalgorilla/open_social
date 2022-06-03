<?php

namespace Drupal\graphql_oauth\Plugin\GraphQL\SchemaExtension;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\SchemaExtensionPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds OAuth directives to the GraphQL API.
 *
 * @SchemaExtension(
 *   id = "graphql_oauth_schema_extension",
 *   name = "OAuth Schema Extension",
 *   description = "GraphQL schema extension that adds OAuth directives."
 * )
 */
class OauthSchemaExtension extends PluginBase implements SchemaExtensionPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * OauthSchemaExtension constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param array $pluginDefinition
   *   The plugin definition array.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   */
  public function __construct(array $configuration, $pluginId, array $pluginDefinition, ModuleHandlerInterface $moduleHandler) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {}

  /**
   * {@inheritdoc}
   */
  public function getBaseDefinition(): ?string {
    $definition = $this->getPluginDefinition();
    $module = $this->moduleHandler->getModule($definition['provider']);
    $path = 'graphql/oauth_directives.graphqls';
    $file = $module->getPath() . '/' . $path;

    return file_get_contents($file) ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensionDefinition(): ?string {
    return NULL;
  }

}

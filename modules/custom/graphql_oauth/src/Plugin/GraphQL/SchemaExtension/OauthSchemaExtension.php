<?php

namespace Drupal\graphql_oauth\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;

/**
 * Adds OAuth directives to the GraphQL API.
 *
 * @SchemaExtension(
 *   id = "graphql_oauth_schema_extension",
 *   name = "OAuth Schema Extension",
 *   description = "GraphQL schema extension that adds OAuth directives.",
 *   schema = ""
 * )
 */
class OauthSchemaExtension extends SdlSchemaExtensionPluginBase {

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

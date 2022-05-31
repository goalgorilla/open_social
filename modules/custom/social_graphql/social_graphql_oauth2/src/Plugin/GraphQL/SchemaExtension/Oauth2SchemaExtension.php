<?php

namespace Drupal\social_graphql_oauth2\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\social_graphql\Plugin\GraphQL\SchemaExtension\SchemaExtensionPluginBase;

/**
 * Adds OAuth2 directives to the Open Social GraphQL API.
 *
 * @SchemaExtension(
 *   id = "social_graphql_oauth2_schema_extension",
 *   name = "Open Social - OAuth2 Schema Extension",
 *   description = "GraphQL schema extension that adds OAuth2 directives.",
 *   schema = "open_social"
 * )
 */
class Oauth2SchemaExtension extends SchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {}

}

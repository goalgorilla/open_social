<?php

namespace Drupal\social_graphql\Plugin\GraphQL\Schema;

use Drupal\graphql\GraphQL\ResolverRegistry;
use Drupal\graphql\Plugin\GraphQL\Schema\SdlSchemaPluginBase;

/**
 * The provider of the schema base for the Open Social GraphQL API.
 *
 * Doesn't do anything itself but provides a target for GraphQL Schema
 * extensions. Schema Extensions should implement `SdlSchemaExtensionPluginBase`
 * and should not subclass this class.
 *
 * This class borrows from the ComposableSchema example but intentionally does
 * not implement the extension configuration that that schema provides. Instead
 * the SdlSchemaPluginBase loads the schema extensions for all Open Social
 * features that are enabled.
 *
 * @Schema(
 *   id = "open_social",
 *   name = "Open Social GraphQL Schema"
 * )
 */
class OpenSocialBaseSchema extends SdlSchemaPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getResolverRegistry() {
    return new ResolverRegistry();
  }

}

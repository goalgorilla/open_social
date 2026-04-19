<?php

namespace Drupal\test_social_graphql_file_upload\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\social_graphql\Plugin\GraphQL\SchemaExtension\SchemaExtensionPluginBase;

/**
 * Adds topic data to the Open Social GraphQL API.
 *
 * @SchemaExtension(
 *   id = "file_upload_test_schema_extension",
 *   name = "Open Social - Test Schema Extension",
 *   description = "GraphQL schema extension to test file upload.",
 *   schema = "open_social"
 * )
 */
class TestSchemaExtension extends SchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry) : void {
    // Intentionally left blank.
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseDefinition() : NULL {
    return NULL;
  }

}

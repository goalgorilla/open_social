<?php

namespace Drupal\social_event\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;
use Drupal\social_graphql\Attribute\SchemaExtensionDependency;

/**
 * Adds event data to the Open Social GraphQL API.
 *
 * @SchemaExtension(
 *   id = "social_event_tagging_schema_extension",
 *   name = "Open Social - Event Tagging Schema Extension",
 *   description = "GraphQL schema extension for tagging in events.",
 *   schema = "open_social"
 * )
 */
#[SchemaExtensionDependency(module: ['social_tagging'])]
class EventTaggingSchemaExtension extends SdlSchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry) : void {
    $builder = new ResolverBuilder();

    $registry->addFieldResolver('Query', 'eventTagCategories',
      $builder->produce('tag_categories_by_content_type')
        ->map('placement', $builder->fromValue('EVENT'))
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseDefinition() {
    // No new base types for this schema extension.
    return NULL;
  }

}

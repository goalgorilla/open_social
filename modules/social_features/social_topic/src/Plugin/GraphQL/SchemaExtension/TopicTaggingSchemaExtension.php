<?php

namespace Drupal\social_topic\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\social_graphql\Attribute\SchemaExtensionDependency;
use Drupal\social_graphql\Plugin\GraphQL\SchemaExtension\SchemaExtensionPluginBase;

/**
 * Adds topic data to the Open Social GraphQL API.
 *
 * @SchemaExtension(
 *   id = "social_topic_tagging_schema_extension",
 *   name = "Open Social - Topic Tagging Schema Extension",
 *   description = "GraphQL schema extension for tagging in topics.",
 *   schema = "open_social"
 * )
 */
#[SchemaExtensionDependency(module: ['social_tagging'])]
class TopicTaggingSchemaExtension extends SchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry) : void {
    $builder = new ResolverBuilder();

    $registry->addFieldResolver('Query', 'topicTagCategories',
      $builder->produce('tag_categories_by_content_type')
        ->map('placement', $builder->fromValue('TOPIC'))
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

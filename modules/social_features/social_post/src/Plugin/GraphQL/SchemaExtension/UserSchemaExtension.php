<?php

declare(strict_types=1);

namespace Drupal\social_post\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\social_graphql\Plugin\GraphQL\SchemaExtension\SchemaExtensionPluginBase;

/**
 * Adds user related variables.
 *
 * @SchemaExtension(
 *   id = "social_post_schema_extension",
 *   name = "Social Post - User Schema Extension",
 *   description = "Schema extension for user object.",
 *   schema = "open_social"
 * )
 */
class UserSchemaExtension extends SchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry) : void {
    $builder = new ResolverBuilder();
    $registry->addFieldResolver('User', 'postsCreated',
      $builder->produce('social_post_posts_created')
        ->map('entity', $builder->fromParent())
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseDefinition() {
    // Skipping social_posts_schema_extension.base.graphqls, as we have nothing
    // to write there.
    return NULL;
  }

}

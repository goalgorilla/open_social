<?php

declare(strict_types=1);

namespace Drupal\test_social_graphql_example_audit\Plugin\GraphQL\Schema;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\GraphQL\Schema\SdlSchemaPluginBase;
use Drupal\social_graphql\GraphQL\ResolverRegistry;
use GraphQL\Language\Source;
use GraphQL\Language\SourceLocation;

/**
 * A schema to test things against.
 *
 * @Schema(
 *   id = "test_schema",
 *   name = "Test Schema"
 * )
 */
class TestSchema extends SdlSchemaPluginBase {

  /**
   * {@inheritdoc}
   */
  public function createResolverRegistry(): ResolverRegistryInterface {
    return new ResolverRegistry();
  }

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    $builder = new ResolverBuilder();

    $registry->addFieldResolver('Query', 'article',
      $builder->produce('entity_load_by_uuid')
        ->map('type', $builder->fromValue('node'))
        ->map('bundles', $builder->fromValue(['article']))
        ->map('uuid', $builder->fromArgument('id'))
    );

    $registry->addFieldResolver('Node', 'id',
      $builder->produce('entity_uuid')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('User', 'displayName',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Article', 'label',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Article', 'author',
      $builder->produce('entity_owner')
        ->map('entity', $builder->fromParent())
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getSchemaDefinition(): Source {
    return new Source(
      <<<EOF
type Query {
  article(id: ID!) : Article
}

interface Node {
  id: ID!
}

type User implements Node {
  id: ID!
  displayName: String
}

type Article implements Node {
  id: ID!
  label: String
  author: User
}
EOF,
      __FILE__,
      new SourceLocation(__LINE__ - 20, 0)
    );
  }

}

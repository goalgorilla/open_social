<?php

namespace Drupal\social_graphql\Plugin\GraphQL\Schema;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\GraphQL\Schema\SdlSchemaPluginBase;
use Drupal\social_graphql\GraphQL\ResolverRegistry;

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

  /**
   * {@inheritdoc}
   */
  public function getSchema(ResolverRegistryInterface $registry) {
    // Add Open Social base types to the schema.
    $this->getBaseSchema($registry);

    // Instantiate the schema and add all extensions.
    return parent::getSchema($registry);
  }

  /**
   * Provides a base schema for Open Social.
   *
   * This ensures that other modules have common types available to them to
   * build on.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The resolver registry.
   */
  protected function getBaseSchema(ResolverRegistryInterface $registry) {
    $builder = new ResolverBuilder();

    // FormattedText fields.
    $registry->addFieldResolver('FormattedText', 'format',
      // TODO: Replace with simplified form once
      //   https://github.com/drupal-graphql/graphql/pull/1089 lands.
      // $builder->fromPath('text', 'format')
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('text'))
        ->map('path', $builder->fromValue('format'))
        ->map('value', $builder->fromParent())
    );

    $registry->addFieldResolver('FormattedText', 'raw',
      // TODO: Replace with simplified form once
      //   https://github.com/drupal-graphql/graphql/pull/1089 lands.
      // $builder->fromPath('text', 'value')
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('text'))
        ->map('path', $builder->fromValue('value'))
        ->map('value', $builder->fromParent())
    );

    // TODO: Implement text processing based on configured format.
    $registry->addFieldResolver('FormattedText', 'processed',
      // TODO: Replace with simplified form once
      //   https://github.com/drupal-graphql/graphql/pull/1089 lands.
      // $builder->fromPath('text', 'processed')
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('text'))
        ->map('path', $builder->fromValue('processed'))
        ->map('value', $builder->fromParent())
    );

    // ConnectionInterface fields.
    $registry->addFieldResolver('ConnectionInterface', 'edges',
      $builder->produce('connection_edges')
        ->map('connection', $builder->fromParent())
    );

    $registry->addFieldResolver('ConnectionInterface', 'pageInfo',
      $builder->produce('connection_page_info')
        ->map('connection', $builder->fromParent())
    );

    // EdgeInterface fields.
    $registry->addFieldResolver('EdgeInterface', 'cursor',
      $builder->produce('edge_cursor')
        ->map('edge', $builder->fromParent())
    );
    $registry->addFieldResolver('EdgeInterface', 'node',
      $builder->produce('edge_node')
        ->map('edge', $builder->fromParent())
    );
  }

}

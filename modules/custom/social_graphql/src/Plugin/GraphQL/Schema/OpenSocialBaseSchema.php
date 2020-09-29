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

    // TextFormat fields.
    $registry->addFieldResolver('TextFormat', 'name',
      // We currently receive the name of the text format from other resolvers
      // so we can pass that along.
      $builder->fromParent()
    );

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

    // Media fields.
    // TODO: Solve this:
    // An issue is that we would like to get the media item from the parent in
    // a generic way so that we can get the URL both from direct media queries
    // e.g. for a media overview, as well as through an association in a field
    // of an entity.
    // The problem comes where the image field as association is an entity
    // reference with metadata, so for the alt text is stored on this field.
    // Entities loaded for an overview would not have this information.
    $registry->addFieldResolver('Media', 'uuid',
      $builder->produce('media_bridge')
        ->map('value', $builder->fromParent())
        ->map('field', $builder->fromValue('uuid'))
    );

    $registry->addFieldResolver('Media', 'url',
      $builder->produce('media_bridge')
        ->map('value', $builder->fromParent())
        ->map('field', $builder->fromValue('url'))
    );

    // Image fields.
    $registry->addFieldResolver('Image', 'title',
      $builder->produce('media_bridge')
        ->map('value', $builder->fromParent())
        ->map('field', $builder->fromValue('title'))
    );

    $registry->addFieldResolver('Image', 'alt',
      $builder->produce('media_bridge')
        ->map('value', $builder->fromParent())
        ->map('field', $builder->fromValue('alt'))
    );

    // Connection fields.
    $registry->addFieldResolver('Connection', 'edges',
      $builder->produce('connection_edges')
        ->map('connection', $builder->fromParent())
    );

    $registry->addFieldResolver('Connection', 'pageInfo',
      $builder->produce('connection_page_info')
        ->map('connection', $builder->fromParent())
    );

    // Edge fields.
    $registry->addFieldResolver('Edge', 'cursor',
      $builder->produce('edge_cursor')
        ->map('edge', $builder->fromParent())
    );
    $registry->addFieldResolver('Edge', 'node',
      $builder->produce('edge_node')
        ->map('edge', $builder->fromParent())
    );
  }

}

<?php

namespace Drupal\social_graphql\Plugin\GraphQL\Schema;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\GraphQL\Schema\SdlSchemaPluginBase;
use Drupal\social_graphql\GraphQL\ResolverRegistry;

/**
 * The provider of the schema base for the Open Social GraphQL API.
 *
 * Provides a target schema for GraphQL Schema extensions. Schema Extensions
 * should implement `SdlSchemaExtensionPluginBase` and should not subclass this
 * class.
 *
 * This class implements the resolver mapping for common data types and
 * interfaces. It uses a modified resolver registry that allows falling back to
 * an interface's field mapping reducing duplication for common object types
 * (such as Connections and Edges).
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
  protected function getExtensions(): array {
    $extensions = parent::getExtensions();
    // Enable OAuth related directives in our schema.
    $oauth_extension_plugin_id = 'graphql_oauth_schema_extension';
    if (!isset($extensions[$oauth_extension_plugin_id])) {
      /** @var \Drupal\graphql\Plugin\SchemaExtensionPluginInterface $plugin */
      $plugin = $this->extensionManager->createInstance($oauth_extension_plugin_id);
      $extensions[$oauth_extension_plugin_id] = $plugin;
    }
    return $extensions;
  }

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
      $builder->fromPath('text', 'format')
    );

    $registry->addFieldResolver('FormattedText', 'raw',
      $builder->fromPath('text', 'value')
    );

    // @todo https://www.drupal.org/project/social/issues/3191613
    $registry->addFieldResolver('FormattedText', 'processed',
      $builder->fromPath('text', 'processed')
    );

    // DateTime fields.
    // @todo https://www.drupal.org/project/social/issues/3191615
    $registry->addFieldResolver('DateTime', 'timestamp',
      $builder->fromParent()
    );

    // Media fields.
    // @todo https://www.drupal.org/project/social/issues/3191617
    $registry->addFieldResolver('Media', 'id',
      $builder->produce('media_bridge')
        ->map('value', $builder->fromParent())
        ->map('field', $builder->fromValue('id'))
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

    $registry->addFieldResolver('Connection', 'nodes',
      $builder->produce('connection_nodes')
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

    // File fields.
    $registry->addFieldResolver('File', 'id',
      $builder->produce('entity_uuid')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('File', 'url',
      $builder->compose(
        $builder->fromPath('entity:file', 'uri.value'),
        $builder->produce('file_url')
          ->map('uri', $builder->fromParent())
      )
    );

    $registry->addFieldResolver('File', 'filename',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:file'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('filename.value'))
    );

    $registry->addFieldResolver('File', 'filemime',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:file'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('filemime.value'))
    );

    $registry->addFieldResolver('File', 'filesize',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:file'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('filesize.value'))
    );

    $registry->addFieldResolver('File', 'created',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:file'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('created.value'))
    );
  }

}

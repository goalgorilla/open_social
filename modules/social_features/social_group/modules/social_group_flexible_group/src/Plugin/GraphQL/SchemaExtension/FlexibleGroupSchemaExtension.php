<?php

namespace Drupal\social_group_flexible_group\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;

/**
 * Adds flexible group data to the Open Social GraphQL API.
 *
 * @SchemaExtension(
 *   id = "social_flexible_group_schema_extension",
 *   name = "Open Social - Flexible Group Schema Extension",
 *   description = "GraphQL schema extension for Open Social flexible group data.",
 *   schema = "open_social"
 * )
 */
class FlexibleGroupSchemaExtension extends SdlSchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry) {
    $builder = new ResolverBuilder();

    $this->addQueryFields($registry, $builder);
    $this->addFlexibleGroupFields($registry, $builder);
  }

  /**
   * Registers type and field resolvers in the shared registry.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The resolver registry.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The resolver builder.
   */
  protected function addFlexibleGroupFields(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $registry->addFieldResolver('FlexibleGroup', 'id',
      $builder->produce('entity_uuid')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('FlexibleGroup', 'title',
      $builder->compose(
        $builder->produce('entity_label')
          ->map('entity', $builder->fromParent()),
        $builder->produce('uppercase')
          ->map('string', $builder->fromParent())
      )
    );

    $registry->addFieldResolver('FlexibleGroup', 'author',
      $builder->produce('entity_owner')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('FlexibleGroup', 'bodyHtml',
      $builder->fromPath('entity:group', 'field_group_description.0.value')
    );

    $registry->addFieldResolver('Event', 'image',
      $builder->produce('field')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue('field_event_image'))
    );

    $registry->addFieldResolver('FlexibleGroup', 'created',
      $builder->fromPath('entity:group', 'created.value')
    );

    $registry->addFieldResolver('FlexibleGroup', 'url',
      $builder->compose(
        $builder->produce('entity_url')
          ->map('entity', $builder->fromParent()),
        $builder->produce('url_path')
          ->map('url', $builder->fromParent())
      )
    );

    $registry->addFieldResolver('FlexibleGroup', 'created',
      $builder->fromPath('entity:group', 'created.value')
    );
  }

  /**
   * Registers type and field resolvers in the query type.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The resolver registry.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The resolver builder.
   */
  protected function addQueryFields(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $registry->addFieldResolver('Query', 'flexibleGroups',
      $builder->produce('query_group')
        ->map('type', $builder->fromValue('flexible_group'))
        ->map('after', $builder->fromArgument('after'))
        ->map('before', $builder->fromArgument('before'))
        ->map('first', $builder->fromArgument('first'))
        ->map('last', $builder->fromArgument('last'))
        ->map('reverse', $builder->fromArgument('reverse'))
        ->map('sortKey', $builder->fromArgument('sortKey'))
    );

    $registry->addFieldResolver('Query', 'flexibleGroup',
      $builder->produce('entity_load_by_uuid')
        ->map('type', $builder->fromValue('group'))
        ->map('bundles', $builder->fromValue(['flexible_group']))
        ->map('uuid', $builder->fromArgument('id'))
    );
  }

}

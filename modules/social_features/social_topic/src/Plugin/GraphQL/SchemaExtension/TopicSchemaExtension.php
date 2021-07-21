<?php

namespace Drupal\social_topic\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;

/**
 * Adds topic data to the Open Social GraphQL API.
 *
 * @SchemaExtension(
 *   id = "social_topic_schema_extension",
 *   name = "Open Social - Topic Schema Extension",
 *   description = "GraphQL schema extension for Open Social topic data.",
 *   schema = "open_social"
 * )
 */
class TopicSchemaExtension extends SdlSchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry) {
    $builder = new ResolverBuilder();

    $this->addQueryFields($registry, $builder);
    $this->addTopicFields($registry, $builder);
  }

  /**
   * Registers type and field resolvers in the shared registry.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The resolver registry.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The resolver builder.
   */
  protected function addTopicFields(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $registry->addFieldResolver('Topic', 'id',
      $builder->produce('entity_uuid')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Topic', 'title',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Topic', 'author',
      $builder->produce('entity_owner')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Topic', 'bodyHtml',
      $builder->compose(
        $builder->produce('field')
          ->map('entity', $builder->fromParent())
          ->map('field', $builder->fromValue('body')),
        $builder->produce('field_renderer')
          ->map('field', $builder->fromParent())
      )
    );

    $registry->addFieldResolver('Topic', 'heroImage',
      $builder->produce('field')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue('field_topic_image'))
    );

    $registry->addFieldResolver('Topic', 'created',
      $builder->produce('entity_created')
        ->map('entity', $builder->fromParent())
        ->map('format', $builder->fromValue('U'))
    );

    $registry->addFieldResolver('Topic', 'comments',
      $builder->produce('query_comments')
        ->map('parent', $builder->fromParent())
        ->map('bundle', $builder->fromValue('comment'))
        ->map('after', $builder->fromArgument('after'))
        ->map('before', $builder->fromArgument('before'))
        ->map('first', $builder->fromArgument('first'))
        ->map('last', $builder->fromArgument('last'))
        ->map('reverse', $builder->fromArgument('reverse'))
        ->map('sortKey', $builder->fromArgument('sortKey'))
    );

    $registry->addFieldResolver('Topic', 'url',
      $builder->compose(
        $builder->produce('social_entity_url')
          ->map('entity', $builder->fromParent())
          ->map('options', $builder->fromValue(['absolute' => TRUE])),
        $builder->produce('url_path')
          ->map('url', $builder->fromParent())
      )
    );

    $registry->addFieldResolver('Topic', 'type',
      $builder->compose(
        $builder->produce('entity_reference')
          ->map('entity', $builder->fromParent())
          ->map('field', $builder->fromValue('field_topic_type')),
        $builder->produce('seek')
          ->map('input', $builder->fromParent())
          ->map('position', $builder->fromValue(0))
      )
    );

    $registry->addFieldResolver('TopicType', 'id',
      $builder->produce('entity_uuid')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('TopicType', 'label',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
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
    $registry->addFieldResolver('Query', 'topics',
      $builder->produce('query_topic')
        ->map('after', $builder->fromArgument('after'))
        ->map('before', $builder->fromArgument('before'))
        ->map('first', $builder->fromArgument('first'))
        ->map('last', $builder->fromArgument('last'))
        ->map('reverse', $builder->fromArgument('reverse'))
        ->map('sortKey', $builder->fromArgument('sortKey'))
    );

    $registry->addFieldResolver('Query', 'topicsByType',
      $builder->produce('query_topic_by_type')
        ->map('type', $builder->fromArgument('type'))
        ->map('after', $builder->fromArgument('after'))
        ->map('before', $builder->fromArgument('before'))
        ->map('first', $builder->fromArgument('first'))
        ->map('last', $builder->fromArgument('last'))
        ->map('reverse', $builder->fromArgument('reverse'))
        ->map('sortKey', $builder->fromArgument('sortKey'))
    );

    $registry->addFieldResolver('Query', 'topic',
      $builder->produce('entity_load_by_uuid')
        ->map('type', $builder->fromValue('node'))
        ->map('bundles', $builder->fromValue(['topic']))
        ->map('uuid', $builder->fromArgument('id'))
    );

    $registry->addFieldResolver('Query', 'topicTypes',
      $builder->produce('taxonomy_load_tree')
        ->map('vid', $builder->fromValue('topic_types'))
        ->map('parent', $builder->fromValue(0))
    );
  }

}

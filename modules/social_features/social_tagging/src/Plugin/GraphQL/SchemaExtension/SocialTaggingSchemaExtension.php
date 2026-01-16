<?php

declare(strict_types=1);

namespace Drupal\social_tagging\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\social_graphql\Plugin\GraphQL\SchemaExtension\SchemaExtensionPluginBase;

/**
 * Adds social tagging related fields.
 *
 * @SchemaExtension(
 *   id = "social_tagging_schema_extension",
 *   name = "Social Tagging - Schema Extension",
 *   description = "Schema extension for content tags and categories.",
 *   schema = "open_social"
 * )
 */
class SocialTaggingSchemaExtension extends SchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    $builder = new ResolverBuilder();

    // Query->contentTagCategory.
    $registry->addFieldResolver('Query', 'contentTagCategory',
      $builder->produce('entity_load_by_uuid')
        ->map('type', $builder->fromValue('taxonomy_term'))
        ->map('bundles', $builder->fromValue(['social_tagging']))
        ->map('uuid', $builder->fromArgument('id'))
    );

    // Query->contentTag.
    $registry->addFieldResolver('Query', 'contentTag',
      $builder->produce('entity_load_by_uuid')
        ->map('type', $builder->fromValue('taxonomy_term'))
        ->map('bundles', $builder->fromValue(['social_tagging']))
        ->map('uuid', $builder->fromArgument('id'))
    );

    // ContentTagCategory fields.
    $registry->addFieldResolver('ContentTagCategory', 'id',
      $builder->produce('entity_uuid')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('ContentTagCategory', 'label',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('ContentTagCategory', 'placement',
      $builder->produce('tag_category_allowed_content_types')
        ->map('term', $builder->fromParent())
    );

    // ContentTagCategory->contentTags.
    $registry->addFieldResolver('ContentTagCategory', 'contentTags',
      $builder->produce('tags_in_category')
        ->map('category', $builder->fromParent())
        ->map('after', $builder->fromArgument('after'))
        ->map('before', $builder->fromArgument('before'))
        ->map('first', $builder->fromArgument('first'))
        ->map('last', $builder->fromArgument('last'))
        ->map('reverse', $builder->fromArgument('reverse'))
    );

    // ContentTag fields.
    $registry->addFieldResolver('ContentTag', 'id',
      $builder->produce('entity_uuid')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('ContentTag', 'label',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
    );

    // ContentTag->parent.
    $registry->addFieldResolver('ContentTag', 'parent',
      $builder->produce('tag_parent_category')
        ->map('tag', $builder->fromParent())
    );

    // ContentTaggable->contentTagCategories.
    $registry->addFieldResolver('ContentTaggable', 'contentTagCategories',
      $builder->produce('entity_tag_categories')
        ->map('entity', $builder->fromParent())
    );

    // ContentTaggableSelectedTagCategories->category.
    // Extract category from the wrapper object.
    $registry->addFieldResolver('ContentTaggableSelectedTagCategories', 'category',
      $builder->produce('tag_category_wrapper_extract_category')
        ->map('wrapper', $builder->fromParent())
    );

    // ContentTaggableSelectedTagCategories->contentTags.
    // Extract both entity and category from the wrapper object.
    $registry->addFieldResolver('ContentTaggableSelectedTagCategories', 'contentTags',
      $builder->produce('entity_tags_by_category')
        ->map('entity', $builder->produce('tag_category_wrapper_extract_entity')
          ->map('wrapper', $builder->fromParent()))
        ->map('category', $builder->produce('tag_category_wrapper_extract_category')
          ->map('wrapper', $builder->fromParent()))
        ->map('after', $builder->fromArgument('after'))
        ->map('before', $builder->fromArgument('before'))
        ->map('first', $builder->fromArgument('first'))
        ->map('last', $builder->fromArgument('last'))
        ->map('reverse', $builder->fromArgument('reverse'))
    );
  }

}

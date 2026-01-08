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
        ->map('access', $builder->fromValue(FALSE))
    );

    // Query->contentTag.
    $registry->addFieldResolver('Query', 'contentTag',
      $builder->produce('entity_load_by_uuid')
        ->map('type', $builder->fromValue('taxonomy_term'))
        ->map('bundles', $builder->fromValue(['social_tagging']))
        ->map('uuid', $builder->fromArgument('id'))
        ->map('access', $builder->fromValue(FALSE))
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
      $builder->produce('content_tag_category_placement')
        ->map('term', $builder->fromParent())
    );

    // ContentTagCategory->contentTags.
    $registry->addFieldResolver('ContentTagCategory', 'contentTags',
      $builder->produce('content_tag_category_tags')
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
      $builder->produce('content_tag_parent')
        ->map('tag', $builder->fromParent())
    );

    // ContentTaggable->contentTagCategories.
    $registry->addFieldResolver('ContentTaggable', 'contentTagCategories',
      $builder->produce('content_taggable_selected_tag_categories')
        ->map('entity', $builder->fromParent())
    );

    // ContentTaggableSelectedTagCategories->category.
    // Extract category from the wrapper object.
    $registry->addFieldResolver('ContentTaggableSelectedTagCategories', 'category',
      $builder->produce('content_taggable_selected_tag_category')
        ->map('wrapper', $builder->fromParent())
    );

    // ContentTaggableSelectedTagCategories->contentTags.
    // Extract both entity and category from the wrapper object.
    $registry->addFieldResolver('ContentTaggableSelectedTagCategories', 'contentTags',
      $builder->produce('content_taggable_selected_tags')
        ->map('entity', $builder->produce('content_taggable_selected_tag_entity')
          ->map('wrapper', $builder->fromParent()))
        ->map('category', $builder->produce('content_taggable_selected_tag_category')
          ->map('wrapper', $builder->fromParent()))
        ->map('after', $builder->fromArgument('after'))
        ->map('before', $builder->fromArgument('before'))
        ->map('first', $builder->fromArgument('first'))
        ->map('last', $builder->fromArgument('last'))
        ->map('reverse', $builder->fromArgument('reverse'))
    );
  }

}

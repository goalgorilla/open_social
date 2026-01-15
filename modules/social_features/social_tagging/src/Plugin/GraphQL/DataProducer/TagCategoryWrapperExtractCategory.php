<?php

declare(strict_types=1);

namespace Drupal\social_tagging\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\taxonomy\TermInterface;

/**
 * Extracts the category from a tag category wrapper object.
 *
 * @DataProducer(
 *   id = "tag_category_wrapper_extract_category",
 *   name = @Translation("Tag Category Wrapper Extract Category"),
 *   description = @Translation("Extracts the category taxonomy term from a tag category wrapper object. The wrapper is created by entity_tag_categories and contains both a category and an entity. This producer returns only the category portion, allowing GraphQL resolvers to access category fields."),
 *   produces = @ContextDefinition("entity:taxonomy_term",
 *     label = @Translation("Category")
 *   ),
 *   consumes = {
 *     "wrapper" = @ContextDefinition("any",
 *       label = @Translation("Wrapper object"),
 *       required = TRUE
 *     ),
 *   }
 * )
 */
class TagCategoryWrapperExtractCategory extends DataProducerPluginBase {

  /**
   * Resolves the category from the wrapper object.
   *
   * @param object $wrapper
   *   The wrapper object containing 'category' and 'entity' properties.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The category term or NULL if not found.
   */
  public function resolve($wrapper): ?TermInterface {
    if (isset($wrapper->category) && $wrapper->category instanceof TermInterface) {
      return $wrapper->category;
    }
    return NULL;
  }

}

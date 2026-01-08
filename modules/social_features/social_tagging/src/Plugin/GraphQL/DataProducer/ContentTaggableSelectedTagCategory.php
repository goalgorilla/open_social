<?php

declare(strict_types=1);

namespace Drupal\social_tagging\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\taxonomy\TermInterface;

/**
 * Extracts the category from a ContentTaggableSelectedTagCategories wrapper.
 *
 * @DataProducer(
 *   id = "content_taggable_selected_tag_category",
 *   name = @Translation("Content Taggable Selected Tag Category"),
 *   description = @Translation("Extracts the category from a ContentTaggableSelectedTagCategories wrapper object."),
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
class ContentTaggableSelectedTagCategory extends DataProducerPluginBase {

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

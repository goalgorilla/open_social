<?php

declare(strict_types=1);

namespace Drupal\social_tagging\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Extracts the entity from a tag category wrapper object.
 *
 * @DataProducer(
 *   id = "tag_category_wrapper_extract_entity",
 *   name = @Translation("Tag Category Wrapper Extract Entity"),
 *   description = @Translation("Extracts the entity from a tag category wrapper object. The wrapper is created by entity_tag_categories and contains both a category and an entity. This producer returns only the entity portion, allowing GraphQL resolvers to access entity fields when resolving nested queries."),
 *   produces = @ContextDefinition("entity",
 *     label = @Translation("Entity")
 *   ),
 *   consumes = {
 *     "wrapper" = @ContextDefinition("any",
 *       label = @Translation("Wrapper object"),
 *       required = TRUE
 *     ),
 *   }
 * )
 */
class TagCategoryWrapperExtractEntity extends DataProducerPluginBase {

  /**
   * Resolves the entity from the wrapper object.
   *
   * @param object $wrapper
   *   The wrapper object containing 'category' and 'entity' properties.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The entity or NULL if not found.
   */
  public function resolve($wrapper): ?ContentEntityInterface {
    if (isset($wrapper->entity) && $wrapper->entity instanceof ContentEntityInterface) {
      return $wrapper->entity;
    }
    return NULL;
  }

}

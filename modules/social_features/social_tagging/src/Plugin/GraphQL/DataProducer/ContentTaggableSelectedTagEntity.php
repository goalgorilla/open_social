<?php

declare(strict_types=1);

namespace Drupal\social_tagging\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Extracts the entity from a ContentTaggableSelectedTagCategories wrapper.
 *
 * @DataProducer(
 *   id = "content_taggable_selected_tag_entity",
 *   name = @Translation("Content Taggable Selected Tag Entity"),
 *   description = @Translation("Extracts the entity from a ContentTaggableSelectedTagCategories wrapper object."),
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
class ContentTaggableSelectedTagEntity extends DataProducerPluginBase {

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

<?php

namespace Drupal\social_album\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityOperations;
use Drupal\views\ResultRow;

/**
 * Renders all operations links for a post.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("social_album_post_operations")
 */
class SocialAlbumEntityOperations extends EntityOperations {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $this->getEntityTranslation($this->getEntity($values), $values);

    return [
      '#lazy_builder' => [
        [
          $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId()),
          'renderLinks',
        ],
        [
          $entity->id(),
          'default',
          $entity->language()->getId(),
          !empty($entity->in_preview),
        ],
      ],
    ];
  }

}

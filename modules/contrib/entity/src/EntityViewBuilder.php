<?php

/**
 * @file
 * Contains \Drupal\entity\EntityViewBuilder.
 */

namespace Drupal\entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder as CoreEntityViewBuilder;

/**
 * Provides a entity view builder with contextual links support
 */
class EntityViewBuilder extends CoreEntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    $entity_type_id = $entity->getEntityTypeId();
    if (($entity instanceof ContentEntityInterface && $entity->isDefaultRevision()) || !$entity->getEntityType()->isRevisionable()) {
      $build['#contextual_links'][$entity_type_id] = [
        'route_parameters' => [
          $entity_type_id => $entity->id()
        ],
      ];
    }
    else {
      $build['#contextual_links'][$entity_type_id . '_revision'] = [
        'route_parameters' => [
          $entity_type_id => $entity->id(),
          $entity_type_id . '_revision' => $entity->getRevisionId(),
        ],
      ];
    }
  }

}

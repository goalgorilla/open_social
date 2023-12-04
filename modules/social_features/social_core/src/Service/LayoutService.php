<?php

namespace Drupal\social_core\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\layout_builder\LayoutEntityHelperTrait;

/**
 * Class LayoutService.
 *
 * @deprecated in social:12.0.0 and is removed from social:13.0.0.
 *  Use function
 *  Drupal\layout_builder\LayoutEntityHelperTrait::isLayoutCompatibleEntity()
 *  instead.
 *
 * @see https://www.drupal.org/project/social/issues/3405938
 * @see https://www.drupal.org/project/socialbase/issues/3405919
 *
 * @package Drupal\social_core\Service
 */
class LayoutService {

  use LayoutEntityHelperTrait;

  /**
   * Determines if an entity can have a layout.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if the entity can have a layout otherwise FALSE.
   */
  public function isTrueLayoutCompatibleEntity(EntityInterface $entity) {
    if (!\Drupal::moduleHandler()->moduleExists('layout_builder')) {
      return FALSE;
    }

    return $this->isLayoutCompatibleEntity($entity);
  }

}

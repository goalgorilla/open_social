<?php

namespace Drupal\social_core\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\layout_builder\LayoutEntityHelperTrait;

/**
 * Class LayoutService.
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

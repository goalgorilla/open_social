<?php

namespace Drupal\social_font\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Font entities.
 */
class FontViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.

    return $data;
  }

}

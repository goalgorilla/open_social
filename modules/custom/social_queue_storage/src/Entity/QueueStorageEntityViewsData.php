<?php

namespace Drupal\social_queue_storage\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Queue storage entity entities.
 */
class QueueStorageEntityViewsData extends EntityViewsData {

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

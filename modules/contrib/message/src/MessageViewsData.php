<?php

/**
 * @file
 * Contains \Drupal\message\MessageViewsData.
 */

namespace Drupal\message;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides the views data for the message entity type.
 */
class MessageViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    return $data;
  }

}

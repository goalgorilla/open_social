<?php

/**
 * @file
 * Contains \Drupal\activity_creator\Entity\Activity.
 */

namespace Drupal\activity_creator\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Activity entities.
 */
class ActivityViewsData extends EntityViewsData implements EntityViewsDataInterface {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['activity_field_data']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Activity'),
      'help' => $this->t('The Activity ID.'),
    );

    return $data;
  }

}

<?php

namespace Drupal\social_event\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Event enrollment entities.
 */
class EventEnrollmentViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['event_enrollment_field_data']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Event enrollment'),
      'help' => $this->t('The Event enrollment ID.'),
    );

    return $data;
  }

}

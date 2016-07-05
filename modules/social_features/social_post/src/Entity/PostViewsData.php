<?php

namespace Drupal\social_post\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Post entities.
 */
class PostViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['post_field_data']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Post'),
      'help' => $this->t('The Post ID.'),
    );

    return $data;
  }

}

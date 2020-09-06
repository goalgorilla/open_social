<?php

namespace Drupal\activity_creator;

use Drupal\Core\Link;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Activity entities.
 *
 * @ingroup activity_creator
 */
class ActivityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [];
    $header['id'] = $this->t('Activity ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];
    /* @var $entity \Drupal\activity_creator\Entity\Activity */
    $row['id'] = $entity->id();
    $row['name'] = Link::fromTextAndUrl($entity->label(), new Url(
      'entity.activity.edit_form', [
        'activity' => $entity->id(),
      ]
    ));
    return $row + parent::buildRow($entity);
  }

}

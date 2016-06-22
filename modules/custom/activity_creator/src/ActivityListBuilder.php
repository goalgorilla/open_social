<?php

/**
 * @file
 * Contains \Drupal\activity_creator\ActivityListBuilder.
 */

namespace Drupal\activity_creator;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Activity entities.
 *
 * @ingroup activity_creator
 */
class ActivityListBuilder extends EntityListBuilder {
  use LinkGeneratorTrait;
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Activity ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\activity_creator\Entity\Activity */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.activity.edit_form', array(
          'activity' => $entity->id(),
        )
      )
    );
    return $row + parent::buildRow($entity);
  }

}

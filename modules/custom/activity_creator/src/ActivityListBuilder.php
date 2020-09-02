<?php

namespace Drupal\activity_creator;

use Drupal\Core\Link;
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
    // TODO: Drupal Rector Notice: Please delete the following comment after you've made any necessary changes.
    // Please manually remove the `use LinkGeneratorTrait;` statement from this class.
    $row['name'] = Link::fromTextAndUrl($entity->label(), new Url(
      'entity.activity.edit_form', [
        'activity' => $entity->id(),
      ]
    ));
    return $row + parent::buildRow($entity);
  }

}

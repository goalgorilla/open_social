<?php

namespace Drupal\social_queue_storage;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Queue storage entity entities.
 *
 * @ingroup social_queue_storage
 */
class QueueStorageEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Queue storage entity ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\social_queue_storage\Entity\QueueStorageEntity $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.queue_storage_entity.edit_form',
      ['queue_storage_entity' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}

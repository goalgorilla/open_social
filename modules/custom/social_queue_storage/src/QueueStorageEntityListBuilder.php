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
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Type');
    $header['owner'] = $this->t('Owner');
    $header['description'] = $this->t('Description');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\social_queue_storage\Entity\QueueStorageEntity $entity */
    $row['id'] = $entity->id();
    $row['name'] = $entity->bundle();
    $row['owner'] = $entity->getOwner()->getDisplayName();

    // Add a description.
    $row_description = $entity->label();
    if ($entity->bundle() === 'email') {
      // When bundle is email, display the email subject.
      $row_description = $entity->get('field_subject')->value;
    }
    $row['description'] = Link::createFromRoute(
      $row_description,
      'entity.queue_storage_entity.canonical',
      ['queue_storage_entity' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}

<?php

/**
 * @file
 * Contains Drupal\mentions\MentionsConfigListBuilder.
 */

namespace Drupal\mentions;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Mentions Type entities.
 */
class MentionsConfigListBuilder extends DraggableListBuilder {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mentions_config_listbuilder_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Machine name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if ($entity->hasLinkTemplate('edit_form')) {
      $operations['edit'] = array(
        'title' => $this->t('Edit'),
        'weight' => 10,
        'url' => $entity->toUrl('edit_form'),
      );
    }

    if ($entity->hasLinkTemplate('delete_form')) {
      $operations['delete'] = array(
        'title' => $this->t('Delete'),
        'weight' => 20,
        'url' => $entity->toUrl('delete_form'),
      );
    }
    return $operations;
  }

}

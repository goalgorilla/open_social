<?php

/**
 * @file
 * Contains \Drupal\profile\ProfileTypeListController.
 */

namespace Drupal\profile;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;

/**
 * List controller for profile types.
 */
class ProfileTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['type'] = t('Profile type');
    $header['registration'] = t('Registration');
    $header['multiple'] = t('Allow multiple profiles');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['type'] = $entity->link();
    $row['registration'] = $entity->getRegistration() ? t('Yes') : t('No');
    $row['multiple'] = $entity->getMultiple() ? t('Yes') : t('No');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    // Place the edit operation after the operations added by field_ui.module
    // which have the weights 15, 20, 25.
    if (isset($operations['edit'])) {
      $operations['edit'] = [
        'title' => t('Edit'),
        'weight' => 30,
        'url' => $entity->toUrl('edit-form'),
      ];
    }
    if (isset($operations['delete'])) {
      $operations['delete'] = [
        'title' => t('Delete'),
        'weight' => 35,
        'url' => $entity->toUrl('delete-form'),
      ];
    }
    // Sort the operations to normalize link order.
    uasort($operations, [
      'Drupal\Component\Utility\SortArray',
      'sortByWeightElement',
    ]);

    return $operations;
  }

}

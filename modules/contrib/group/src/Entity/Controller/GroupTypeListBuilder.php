<?php

/**
 * @file
 * Contains \Drupal\group\GroupTypeListBuilder.
 */

namespace Drupal\group\Entity\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of group type entities.
 *
 * @see \Drupal\group\Entity\GroupType
 */
class GroupTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Name');
    $header['description'] = [
      'data' => t('Description'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\group\Entity\GroupTypeInterface $entity */
    $row['label'] = [
      'data' => $entity->label(),
      'class' => ['menu-label'],
    ];
    $row['description']['data'] = ['#markup' => $entity->getDescription()];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    // Place the group type specific operations after the operations added by
    // field_ui.module which have the weights 15, 20, 25.
    if (isset($operations['edit'])) {
      $operations['edit']['weight'] = 30;
    }

    if ($entity->hasLinkTemplate('permissions-form')) {
      $operations['permissions'] = [
        'title' => t('Edit permissions'),
        'weight' => 35,
        'url' => $entity->toUrl('permissions-form'),
      ];
    }

    // Can't use a link template because the group roles route doesn't start
    // with entity.group_type, see: https://www.drupal.org/node/2645136.
    $operations['group_roles'] = [
      'title' => t('Edit group roles'),
      'weight' => 40,
      'url' => Url::fromRoute('entity.group_role.collection', ['group_type' => $entity->id()]),
    ];

    if ($entity->hasLinkTemplate('content-plugins')) {
      $operations['content'] = [
        'title' => t('Set available content'),
        'weight' => 45,
        'url' => $entity->toUrl('content-plugins'),
      ];
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No group types available. <a href="@link">Add group type</a>.', [
      '@link' => Url::fromRoute('entity.group_type.add_form')->toString()
    ]);
    return $build;
  }

}

<?php

/**
 * @file
 * Contains \Drupal\group\Entity\Controller\GroupListBuilder.
 */

namespace Drupal\group\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for group entities.
 *
 * @ingroup group
 */
class GroupListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['gid'] = $this->t('Group ID');
    $header['name'] = $this->t('Name');
    $header['type'] = $this->t('Type');
    $header['uid'] = $this->t('Owner');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\group\Entity\GroupInterface $entity */
    $row['id'] = $entity->id();
    // EntityListBuilder sets the table rows using the #rows property, so we
    // need to add the render array using the 'data' key.
    $row['name']['data'] = $entity->toLink()->toRenderable();
    $row['type'] = $entity->getGroupType()->label();
    $row['uid'] = $entity->uid->entity->label();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('There are no groups yet.');
    return $build;
  }

}

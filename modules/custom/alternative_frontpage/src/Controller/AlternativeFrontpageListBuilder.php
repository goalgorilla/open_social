<?php

namespace Drupal\alternative_frontpage\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Example.
 */
class AlternativeFrontpageListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['path'] = $this->t('Path');
    $header['roles_target_id'] = $this->t('Role');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['path'] = $entity->path;
    $row['roles_target_id'] = $entity->roles_target_id;

    return $row + parent::buildRow($entity);
  }

}

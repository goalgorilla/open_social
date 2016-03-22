<?php

/**
 * @file
 * Contains Drupal\template_mapper\TemplateMappingListBuilder.
 */

namespace Drupal\template_mapper;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Template mapping entities.
 */
class TemplateMappingListBuilder extends ConfigEntityListBuilder {
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Template mapping');
    $header['id'] = $this->t('Pre-existing theme hook suggestion');
    $header['mapping'] = $this->t('Replacement suggestion');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    $row['id'] = $entity->id();
    $row['mapping'] = $entity->getMapping();
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }


  /**
   * {@inheritdoc}
   *
   * Builds the entity listing as renderable array for table.html.twig.
   *
   * @todo Add a link to add a new item to the #empty text.
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No template mappings have been added yet.');

    return $build;
  }

}

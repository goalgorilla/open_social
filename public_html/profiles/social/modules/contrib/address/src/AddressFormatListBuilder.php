<?php

/**
 * @file
 * Contains \Drupal\address\AddressFormatListBuilder.
 */

namespace Drupal\address;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of address formats.
 */
class AddressFormatListBuilder extends ConfigEntityListBuilder {

  /**
   * The number of address formats to list per page.
   *
   * The CLDR country list contains 253 countries, this limit ensures there's
   * no pagination even if an address format is created for each one.
   *
   * @var int
   */
  protected $limit = 260;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['country'] = $this->t('Country');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['country'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

}

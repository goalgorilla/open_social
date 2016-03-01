<?php

/**
 * @file
 * Contains \Drupal\address\AddressFormatStorage.
 */

namespace Drupal\address;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityStorageException;

/**
 * Defines the address format storage.
 */
class AddressFormatStorage extends ConfigEntityStorage {

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities) {
    // ZZ is the fallback address format and it must always be present.
    if (array_key_exists('ZZ', $entities) && current($entities)->isUninstalling() === FALSE) {
      throw new EntityStorageException("The 'ZZ' address format can't be deleted.");
    }
    parent::doDelete($entities);
  }

}

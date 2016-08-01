<?php

namespace Drupal\address;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the address format entity type.
 *
 * @see \Drupal\address\Entity\AddressFormat
 */
class AddressFormatAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // ZZ is the fallback address format and it must always be present.
    if (($operation == 'delete') && ($entity->id() == 'ZZ')) {
      return AccessResult::forbidden();
    }
    else {
      return parent::checkAccess($entity, $operation, $account);
    }
  }

}

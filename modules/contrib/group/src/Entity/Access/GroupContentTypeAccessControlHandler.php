<?php

/**
 * @file
 * Contains \Drupal\group\Entity\Access\GroupContentTypeAccessControlHandler.
 */

namespace Drupal\group\Entity\Access;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the group content type entity type.
 *
 * @see \Drupal\group\Entity\GroupContentType
 */
class GroupContentTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\group\Entity\GroupContentTypeInterface $entity */
    if ($operation == 'delete') {
      return parent::checkAccess($entity, $operation, $account)->addCacheableDependency($entity);
    }
    return parent::checkAccess($entity, $operation, $account);
  }

}

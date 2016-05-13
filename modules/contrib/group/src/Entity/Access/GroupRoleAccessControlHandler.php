<?php

/**
 * @file
 * Contains \Drupal\group\Entity\Access\GroupRoleAccessControlHandler.
 */

namespace Drupal\group\Entity\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the group role entity type.
 *
 * @see \Drupal\group\Entity\GroupRole
 */
class GroupRoleAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\group\Entity\GroupRoleInterface $entity */
    if ($operation == 'delete') {
      return parent::checkAccess($entity, $operation, $account)->addCacheableDependency($entity);
    }
    return parent::checkAccess($entity, $operation, $account);
  }

}

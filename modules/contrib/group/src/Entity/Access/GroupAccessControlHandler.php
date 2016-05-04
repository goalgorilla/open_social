<?php

/**
 * @file
 * Contains \Drupal\group\Entity\Access\GroupAccessControlHandler
 */

namespace Drupal\group\Entity\Access;

use Drupal\group\Access\GroupAccessResult;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Group entity.
 *
 * @see \Drupal\group\Entity\Group.
 */
class GroupAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return GroupAccessResult::allowedIfHasGroupPermission($entity, $account, 'view group');

      case 'update':
        return GroupAccessResult::allowedIfHasGroupPermission($entity, $account, 'edit group');

      case 'delete':
        return GroupAccessResult::allowedIfHasGroupPermission($entity, $account, 'delete group');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIf($account->hasPermission('create ' . $entity_bundle . ' group'))->cachePerPermissions();
  }

}

<?php

namespace Drupal\social_queue_storage;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Queue storage entity entity.
 *
 * @see \Drupal\social_queue_storage\Entity\QueueStorageEntity.
 */
class QueueStorageEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\social_queue_storage\Entity\QueueStorageEntityInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished queue storage entity entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published queue storage entity entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit queue storage entity entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete queue storage entity entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add queue storage entity entities');
  }

}

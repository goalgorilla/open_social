<?php

/**
 * @file
 * Contains \Drupal\social_post\PostAccessControlHandler.
 */

namespace Drupal\social_post;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Post entity.
 *
 * @see \Drupal\social_post\Entity\Post.
 */
class PostAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\social_post\PostInterface $entity */
    switch ($operation) {
      case 'view':
        // Public = ALL
        $visibility = $entity->field_visibility->value;

        switch ($visibility) {
          // Recipient.
          case "0":
            if (AccessResult::allowedIfHasPermission($account, 'view community posts')) {
              return $this->checkDefaultAccess($entity, $operation, $account);
            }
            return AccessResult::forbidden();
            break;

          // Public.
          case "1":
            if (AccessResult::allowedIfHasPermission($account, 'view public posts')) {
              return $this->checkDefaultAccess($entity, $operation, $account);
            }
            return AccessResult::forbidden();

          // Community.
          case "2":
            if (AccessResult::allowedIfHasPermission($account, 'view community posts')) {
              return $this->checkDefaultAccess($entity, $operation, $account);
            }
            return AccessResult::forbidden();
        }

      case 'update':
        return $this->checkDefaultAccess($entity, $operation, $account);

      case 'delete':
        return $this->checkDefaultAccess($entity, $operation, $account);
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  protected function checkDefaultAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished post entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published post entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit post entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete post entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add post entities');
  }

}

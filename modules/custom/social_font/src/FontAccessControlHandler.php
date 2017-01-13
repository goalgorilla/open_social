<?php

namespace Drupal\social_font;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Font entity.
 *
 * @see \Drupal\social_font\Entity\Font.
 */
class FontAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\social_font\Entity\FontInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished font entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published font entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit font entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete font entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add font entities');
  }

}

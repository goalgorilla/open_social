<?php

namespace Drupal\social_featured_items\Hooks;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\hux\Attribute\Hook;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Hooks related to the entity access.
 */
final class EntityAccess {

  /**
   * Custom access for featured items paragraph.
   *
   * This method provides access control for featured_items and featured_item
   * paragraph types, allowing sitemanagers and contentmanagers to edit these
   * paragraph types regardless of ownership.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $entity
   *   The paragraph entity.
   * @param string $operation
   *   The operation of access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account of user.
   */
  #[Hook('paragraph_access')]
  public function featuredItemsParagraphAccess(ParagraphInterface $entity, string $operation, AccountInterface $account): AccessResultInterface {
    // Only handle supported operations.
    if (!in_array($operation, ['view', 'update', 'delete'], TRUE)) {
      return AccessResult::neutral()->addCacheableDependency($entity);
    }

    // Only handle featured_items and featured_item paragraph types.
    $supportedParagraphTypes = ['featured_items', 'featured_item'];
    if (!in_array($entity->bundle(), $supportedParagraphTypes, TRUE)) {
      return AccessResult::neutral()->addCacheableDependency($entity);
    }

    // Allow access for users with administrative permissions.
    if ($account->hasPermission('administer paragraphs')) {
      return AccessResult::allowedIfHasPermission($account, 'administer paragraphs')
        ->addCacheableDependency($entity);
    }

    // Role-based check for remaining users.
    $allowedRoles = ['sitemanager', 'contentmanager'];
    $hasAllowedRole = (bool) array_intersect($account->getRoles(), $allowedRoles);

    return AccessResult::allowedIf($hasAllowedRole)
      ->addCacheContexts(['user.roles'])
      ->addCacheableDependency($entity);
  }

}

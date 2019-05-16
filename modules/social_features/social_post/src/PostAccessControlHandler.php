<?php

namespace Drupal\social_post;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\group\Entity\GroupInterface;

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
        // Public = ALL.
        if ($entity->isPublished()) {
          $visibility = $entity->field_visibility->value;

          switch ($visibility) {
            // Recipient.
            case "0":

              if (AccessResult::allowedIfHasPermission($account, 'view community posts')->isAllowed()) {
                // Check if the post has been posted in a group.
                $group_id = $entity->field_recipient_group->target_id;
                if ($group_id) {
                  $group = entity_load('group', $group_id);
                  if ($group !== NULL && $group->hasPermission('access posts in group', $account) && $this->checkDefaultAccess($entity, $operation, $account)) {
                    return AccessResult::allowed();
                  }
                  else {
                    return AccessResult::forbidden();
                  }
                }
                // Fallback for invalid groups or if there is no group
                // recipient.
                return $this->checkDefaultAccess($entity, $operation, $account);
              }
              return AccessResult::forbidden();

            // Public.
            case "1":
              if (AccessResult::allowedIfHasPermission($account, 'view public posts')->isAllowed()) {
                return $this->checkDefaultAccess($entity, $operation, $account);
              }
              return AccessResult::forbidden();

            // Community.
            case "2":
              if (AccessResult::allowedIfHasPermission($account, 'view community posts')->isAllowed()) {
                return $this->checkDefaultAccess($entity, $operation, $account);
              }
              return AccessResult::forbidden();

            // Group.
            case "3":
              // Check if the post has been posted in a group.
              $group_id = $entity->field_recipient_group->target_id;

              if ($group_id !== NULL) {
                /* @var \Drupal\group\Entity\Group $group */
                $group = entity_load('group', $group_id);
              }

              if ($group !== NULL) {
                if ($group->hasPermission('access posts in group', $account) && $this->checkDefaultAccess($entity, $operation, $account)) {
                  if ($group->getMember($account)) {
                    return AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity);
                  }
                }
                return AccessResult::forbidden();
              }
              return AccessResult::forbidden();

          }
        }
        else {
          // Fetch information from the entity object if possible.
          $uid = $entity->getOwnerId();
          // Check if authors can view their own unpublished posts.
          if ($operation === 'view' && $account->hasPermission('view own unpublished post entities') && $account->isAuthenticated() && $account->id() == $uid) {
            return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
          }
        }

      case 'update':
        // Check if the user has permission to edit any or own post entities.
        if ($account->hasPermission('edit any post entities', $account)) {
          return AccessResult::allowed();
        }
        elseif ($account->hasPermission('edit own post entities', $account) && ($account->id() == $entity->getOwnerId())) {
          return AccessResult::allowed();
        }
        return AccessResult::forbidden();

      case 'delete':
        // Check if the user has permission to delete any or own post entities.
        if ($account->hasPermission('delete any post entities', $account)) {
          return AccessResult::allowed();
        }
        elseif ($account->hasPermission('delete own post entities', $account) && ($account->id() == $entity->getOwnerId())) {
          return AccessResult::allowed();
        }
        return AccessResult::forbidden();
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkDefaultAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          if ($account->hasPermission('view own unpublished post entities', $account) && ($account->id() == $entity->getOwnerId())) {
            return AccessResult::allowed();
          }
          return AccessResult::allowedIfHasPermission($account, 'view unpublished post entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published post entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit any post entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete any post entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // If group context is active.
    $group = _social_group_get_current_group();
    if ($group instanceof GroupInterface) {
      if ($group->hasPermission('add post entities in group', $account)) {
        if ($group->getGroupType()->id() === 'public_group') {
          $config = \Drupal::config('entity_access_by_field.settings');
          if ($config->get('disable_public_visibility') === 1 && !$account->hasPermission('override disabled public visibility')) {
            return AccessResult::forbidden();
          }
        }
        return AccessResult::allowed();
      }
      else {
        // Not allowed to create posts.
        return AccessResult::forbidden();
      }
    }
    // Fallback.
    return AccessResult::allowedIfHasPermission($account, 'add post entities');
  }

}

<?php

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
        // Public = ALL.
        $visibility = $entity->field_visibility->value;

        switch ($visibility) {
          // Recipient.
          case "0":

            if (AccessResult::allowedIfHasPermission($account, 'view community posts')->isAllowed()) {
              // Check if the post has been posted in a group.
              $group_id = $entity->field_recipient_group->target_id;
              if ($group_id) {
                $group = entity_load('group', $group_id);
                if ($group->hasPermission('access posts in group', $account) && $this->checkDefaultAccess($entity, $operation, $account)) {
                  return AccessResult::allowed();
                }
                else {
                  return AccessResult::forbidden();
                }
              }
              // Fallback for invalid groups or if there is no group recipient.
              return $this->checkDefaultAccess($entity, $operation, $account);
            }
            return AccessResult::forbidden();

          break;

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
   *
   */
  protected function checkDefaultAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
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
    $group = \Drupal::routeMatch()->getParameter('group');
    if ($group) {
      if ($group->hasPermission('add post entities in group', $account)) {
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

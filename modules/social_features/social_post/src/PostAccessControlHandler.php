<?php

namespace Drupal\social_post;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access controller for the Post entity.
 *
 * @see \Drupal\social_post\Entity\Post.
 */
class PostAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')
    );
  }

  /**
   * PostAccessControlHandler constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type interface.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($entity_type);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\social_post\Entity\PostInterface $entity */

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
                $permission = 'access posts in group';
                if ($group->hasPermission($permission, $account) && $this->checkDefaultAccess($entity, $operation, $account)) {
                  if ($group->getGroupType()->id() === 'flexible_group') {
                    // User has access if outsider with manager role or member.
                    $account_roles = $account->getRoles();
                    foreach (['sitemanager', 'contentmanager', 'administrator'] as $manager_role) {
                      if (in_array($manager_role, $account_roles)) {
                        return AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity);
                      }
                    }

                    $group_role_storage = $this->entityTypeManager->getStorage('group_role');
                    $group_roles = $group_role_storage->loadByUserAndGroup($account, $group);
                    /** @var \Drupal\group\Entity\GroupRoleInterface $group_role */
                    foreach ($group_roles as $group_role) {
                      if ($group_role->isOutsider()) {
                        return AccessResult::forbidden()->cachePerUser()->addCacheableDependency($entity);
                      }
                    }
                    if ($group->getMember($account)) {
                      return AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity);
                    }
                  }
                  return AccessResult::allowed();
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
        return AccessResult::neutral();

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
    $access = AccessResult::allowedIfHasPermission($account, 'add post entities');

    if ($entity_bundle !== NULL) {
      return $access->orIf(AccessResult::allowedIfHasPermission($account, "add $entity_bundle post entities"));
    }

    return $access;
  }

}

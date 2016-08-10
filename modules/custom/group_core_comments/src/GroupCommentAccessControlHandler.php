<?php

namespace Drupal\group_core_comments;

use Drupal\Core\Access\AccessResult;
use Drupal\comment\CommentAccessControlHandler;
use Drupal\group\Entity\GroupContent;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the comment entity type.
 *
 * @see \Drupal\comment\Entity\Comment
 */
class GroupCommentAccessControlHandler extends CommentAccessControlHandler {

  // @TODO implement setting to make it possible overridden on per-group basis.

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\comment\CommentInterface|\Drupal\user\EntityOwnerInterface $entity */

    $parent_access = parent::checkAccess($entity, $operation, $account);

    $commented_entity = $entity->getCommentedEntity();
    $group_contents = GroupContent::loadByEntity($commented_entity);

    $administer_access = $this->getPermissionInGroups('administer comments', $account, $group_contents);
    if ($administer_access->isAllowed()) {
      $access = AccessResult::allowed()->cachePerPermissions();
      return ($operation != 'view') ? $access : $access->andIf($entity->getCommentedEntity()->access($operation, $account, TRUE));
    }

    // @TODO only react on if $parent === allowed Is this good/safe enough?
    if ($parent_access->isAllowed()) {
      // Only react if it is actually posted inside a group.
      if (!empty($group_contents)) {
        switch ($operation) {
          case 'view':
            return $this->getPermissionInGroups('access comments', $account, $group_contents);
          case 'update':
            return $this->getPermissionInGroups('edit own comments', $account, $group_contents);
          default:
            // No opinion.
            return AccessResult::neutral()->cachePerPermissions();
        }
      }
    }
    // Fallback.
    return $parent_access;
  }

  protected function getPermissionInGroups($perm, AccountInterface $account, $group_contents) {

    // Only when you have permission to view the comments.
    foreach ($group_contents as $group_content) {
      /** @var \Drupal\group\Entity\GroupContent $group_content */
      $group = $group_content->getGroup();
      /** @var \Drupal\group\Entity\Group $group */
      if ($group->hasPermission($perm, $account)) {
        return AccessResult::allowed()->cachePerUser();
      }
    }
    // Fallback.
    return AccessResult::forbidden()->cachePerUser();
  }

}

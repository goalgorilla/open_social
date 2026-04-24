<?php

namespace Drupal\group_core_comments;

use Drupal\comment\CommentAccessControlHandler;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupRelationship;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for the comment entity type.
 *
 * @see \Drupal\comment\Entity\Comment
 *
 * @todo Implement setting to make it possible overridden on per-group basis.
 */
class GroupCommentAccessControlHandler extends CommentAccessControlHandler implements EntityHandlerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')
    );
  }

  /**
   * Constructs a GroupCommentAccessControlHandler.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\comment\CommentInterface|\Drupal\user\EntityOwnerInterface $entity */

    $parent_access = parent::checkAccess($entity, $operation, $account);

    $commented_entity = $entity->getCommentedEntity();
    if (!($commented_entity instanceof ContentEntityInterface)) {
      return AccessResult::neutral();
    }
    $groups = $this->getGroupsForCommentedEntity($commented_entity);

    // Check for 'delete all comments' permission in case content is not from
    // group.
    if ($groups === [] && $account->hasPermission('delete all comments')) {
      $administer_access = AccessResult::allowed();
    }
    else {
      $administer_access = $this->getPermissionInGroups('administer comments', $account, $groups);
    }

    if ($administer_access->isAllowed()) {
      $access = AccessResult::allowed()->cachePerPermissions();
      return ($operation != 'view') ? $access : $access->andIf($entity->getCommentedEntity()->access($operation, $account, TRUE));
    }

    // @todo Only react on if $parent === allowed Is this good/safe enough?
    if ($parent_access->isAllowed()) {
      // Only react if it is actually posted inside a group.
      if ($groups !== []) {
        switch ($operation) {
          case 'view':
            return $this->getPermissionInGroups('access comments', $account, $groups);

          case 'update':
            return $this->getPermissionInGroups('edit own comments', $account, $groups);

          default:
            // No opinion.
            return AccessResult::neutral()->cachePerPermissions();
        }
      }
    }
    // Fallback.
    return $parent_access;
  }

  /**
   * Gets groups that grant comment permissions for the commented entity.
   *
   * @return \Drupal\group\Entity\GroupInterface[]
   *   A list of groups, keyed by group id.
   */
  protected function getGroupsForCommentedEntity(ContentEntityInterface $commented_entity): array {
    $groups = [];
    foreach (GroupRelationship::loadByEntity($commented_entity) as $group_content) {
      $group = $group_content->getGroup();
      $groups[$group->id()] = $group;
    }
    if ($groups === [] && $commented_entity->getEntityTypeId() === 'post' && $commented_entity->hasField('field_recipient_group') && !$commented_entity->get('field_recipient_group')->isEmpty()) {
      $gid = $commented_entity->get('field_recipient_group')->target_id;
      $group = $this->entityTypeManager->getStorage('group')->load($gid);
      if ($group !== NULL) {
        $groups[$group->id()] = $group;
      }
    }
    return $groups;
  }

  /**
   * Checks if account was granted permission in at least one group.
   *
   * @param string $perm
   *   The group permission machine name to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   * @param \Drupal\group\Entity\GroupInterface[] $groups
   *   Groups keyed by group id.
   */
  protected function getPermissionInGroups(string $perm, AccountInterface $account, array $groups): AccessResultInterface {
    foreach ($groups as $group) {
      if ($group->hasPermission($perm, $account)) {
        return AccessResult::allowed()
          ->cachePerUser()
          ->addCacheableDependency($group);
      }
    }
    return AccessResult::forbidden()->cachePerUser();
  }

}

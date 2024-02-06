<?php

namespace Drupal\social_event_managers\Plugin\Group\RelationHandlerDefault;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Plugin\Group\RelationHandler\AccessControlInterface;
use Drupal\group\Plugin\Group\RelationHandler\AccessControlTrait;
use Drupal\group\Plugin\Group\RelationHandlerDefault\AccessControl;
use Drupal\social_event_managers\SocialEventManagersAccessHelper;

/**
 * Provides access control for Event GroupContent entities.
 *
 * @todo Check if the access handler not need anymore if favor of "social_event_managers_node_access()".
 */
class EventsGroupContentAccessControl implements AccessControlInterface {

  use AccessControlTrait;

  /**
   * Constructs a new GroupMembershipAccessControl.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandlerDefault\AccessControl $parent
   *   The parent access control handler.
   */
  public function __construct(AccessControlInterface $parent) {
    $this->parent = $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account, $return_as_object = FALSE) {
    // We only care about Update (/edit) of the Event content.
    if ($operation !== 'update') {
      $this->parent->entityAccess($entity, $operation, $account, $return_as_object);
    }

    /** @var \Drupal\group\Entity\Storage\GroupRelationshipStorage $storage */
    $storage = $this->entityTypeManager()->getStorage('group_content');
    $group_contents = $storage->loadByEntity($entity, $this->pluginId);

    // If this plugin is not being used by the entity, we have nothing to say.
    if (empty($group_contents)) {
      return AccessResult::neutral();
    }

    // We need to determine if the user has access based on group permissions.
    $group_based_access = $this->parent->entityAccess($entity, $operation, $account, $return_as_object);

    // Only when the access result is False we need to override,
    // when a user already has access based on Group relation we're good.
    if (!$group_based_access instanceof AccessResultForbidden) {
      return $group_based_access;
    }

    // Based on the EventManager access we can determine if a user
    // is the owner, or an event manager/organizer and give out
    // permissions.
    foreach ($group_contents as $group_content) {
      /** @var \Drupal\node\NodeInterface $node */
      $node = $group_content->getEntity();
      $result = SocialEventManagersAccessHelper::getEntityAccessResult($node, $operation, $account);
      if ($result->isAllowed()) {
        break;
      }
    }

    if (!isset($result)) {
      return $group_based_access;
    }

    // If we did not allow access, we need to explicitly forbid access to avoid
    // other modules from granting access where Group promised the entity would
    // be inaccessible.
    if (!$result->isAllowed()) {
      $result = AccessResult::forbidden()->addCacheContexts(['user.group_permissions']);
    }

    $result->cachePerUser();

    return $return_as_object ? $result : $result->isAllowed();
  }

}

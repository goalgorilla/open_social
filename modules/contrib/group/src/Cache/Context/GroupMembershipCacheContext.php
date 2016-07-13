<?php

/**
 * @file
 * Contains \Drupal\group\Cache\Context\GroupMembershipCacheContext.
 */

namespace Drupal\group\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;

/**
 * Defines a cache context for "per group membership" caching.
 *
 * Please note: This cache context uses the group from the current route as the
 * value object to work with. This context is therefore only to be used with
 * data that was based on the group from the route. You can retrieve it using
 * the 'entity:group' context provided by the 'group.group_route_context'
 * service. See an example at: \Drupal\group\Plugin\Block\GroupOperationsBlock.
 *
 * Cache context ID: 'group_membership'.
 */
class GroupMembershipCacheContext extends GroupMembershipCacheContextBase implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Group membership');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    // If there was no existing group on the route, there can be no membership.
    if (!$this->hasExistingGroup()) {
      return 'none';
    }

    // If there is a membership, we return the membership ID.
    if ($group_membership = $this->group->getMember($this->user)) {
      return $group_membership->getGroupContent()->id();
    }

    // Otherwise, return the ID of the 'outsider' or 'anonymous' group role,
    // depending on the user. This is necessary to have a unique identifier to
    // distinguish between 'outsider' or 'anonymous' users for different group
    // types.
    return $this->user->isAnonymous()
      ? $this->group->getGroupType()->getAnonymousRoleId()
      : $this->group->getGroupType()->getOutsiderRoleId();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    // You can't update a group content's ID. So even if somehow this top-level
    // cache context got optimized away, it does not need to set a cache tag for
    // a group content entity as the ID is not invalidated by a save.
    return new CacheableMetadata();
  }

}

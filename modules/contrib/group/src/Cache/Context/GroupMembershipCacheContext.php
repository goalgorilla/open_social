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
    // If there was no group on the route, there can be no membership.
    if (empty($this->group)) {
      return 'none';
    }

    // If there is a membership, we return the membership ID.
    if ($group_membership = $this->group->getMember($this->user)) {
      return $group_membership->getGroupContent()->id();
    }

    // Otherwise, return 'outsider' or 'anonymous' depending on the user.
    return $this->user->id() == 0 ? 'anonymous' : 'outsider';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    $cacheable_metadata = new CacheableMetadata();

    if (!empty($this->group) && $group_membership = $this->group->getMember($this->user)) {
      // This needs to be invalidated whenever the group membership is updated.
      $cacheable_metadata->setCacheTags($group_membership->getGroupContent()->getCacheTags());
    }

    return $cacheable_metadata;
  }

}

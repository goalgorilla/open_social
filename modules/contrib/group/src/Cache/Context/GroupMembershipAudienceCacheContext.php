<?php

/**
 * @file
 * Contains \Drupal\group\Cache\Context\GroupMembershipAudienceCacheContext.
 */

namespace Drupal\group\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;

/**
 * Defines a cache context for "per group audience" caching.
 *
 * The idea behind this cache context is to allow a piece of content to vary by
 * the fact that a user is a member, outsider or anonymous user with regard to
 * the group.
 *
 * Please note: This cache context uses the group from the current route as the
 * value object to work with. This context is therefore only to be used with
 * data that was based on the group from the route. You can retrieve it using
 * the 'entity:group' context provided by the 'group.group_route_context'
 * service. See an example at: \Drupal\group\Plugin\Block\GroupOperationsBlock.
 *
 * Cache context ID: 'group_membership.audience'.
 */
class GroupMembershipAudienceCacheContext extends GroupMembershipCacheContextBase implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Group membership audience');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    // If there was no existing group on the route, there can be no membership.
    if (!$this->hasExistingGroup()) {
      return 'none';
    }

    // If there is a membership, we return 'member.
    if ($this->group->getMember($this->user)) {
      return 'member';
    }

    // Otherwise, return 'outsider' or 'anonymous' depending on the user.
    return $this->user->id() == 0 ? 'anonymous' : 'outsider';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    // There is nothing that could affect this cache context should it be
    // optimized away, so return an empty cacheable metadata object.
    return new CacheableMetadata();
  }

}

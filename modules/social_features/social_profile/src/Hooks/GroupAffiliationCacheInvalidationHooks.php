<?php

namespace Drupal\social_profile\Hooks;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupMembership;
use Drupal\group\Entity\GroupMembershipTrait;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\hux\Attribute\Hook;
use Drupal\social_profile\GroupAffiliation;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Group affiliation cache invalidation hooks class.
 *
 * @internal
 */
class GroupAffiliationCacheInvalidationHooks implements ContainerInjectionInterface {

  use GroupMembershipTrait;

  /**
   * GroupAffiliationCacheInvalidationHooks constructor.
   *
   * @param \Drupal\social_profile\GroupAffiliation $groupAffiliation
   *   Group affiliation service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   *   The cache tags invalidator.
   */
  public function __construct(
    protected GroupAffiliation $groupAffiliation,
    protected CacheBackendInterface $cacheBackend,
    protected CacheTagsInvalidatorInterface $cacheTagsInvalidator,
  ) {}

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('social_profile.group_affiliation'),
      $container->get('cache.default'),
      $container->get('cache_tags.invalidator'),
    );
  }

  /**
   * Invalidates the caches related to GroupAffiliationWidget select options.
   *
   * This function is needed, to invalidate select options for
   * GroupAffiliationWidget. The items on the mentioned select option list are
   * groups which have affiliation enabled and user is the member of the group.
   *
   * Cache invalidation is triggered only for memberships, where group type have
   * affiliation enabled.
   *
   * Why this is needed:
   *  - If membership is created.
   *  - If membership is changed.
   *  - If membership is deleted (if group with a membership is deleted).
   *
   * Custom cache tags:
   *  - group_affiliation_options_by_user:{$user_id}
   *  - group_content_list:plugin:group_membership:entity:{$user_id}:group_type:{$group_type}
   *
   * Custom cache ids:
   *  - group_affiliation_options_by_user:{$user_id}
   *
   * @param \Drupal\group\Entity\GroupRelationshipInterface $entity
   *   Group relationship entity.
   *
   * @return void
   *   Return void.
   */
  #[
    Hook('group_content_insert'),
    Hook('group_content_update'),
    Hook('group_content_delete')
  ]
  public function groupMembershipChange(GroupRelationshipInterface $entity): void {
    if (!$entity instanceof GroupMembership) {

      // Early return if group content is not group membership.
      return;
    }

    $group_types = $this->groupAffiliation->getAffiliationEnabledGroupTypes();
    if (!in_array($entity->getGroup()->getGroupType()->id(), array_keys($group_types))) {

      // Early return if group type does not have affiliation enabled.
      return;
    }

    $user_id = $entity->getEntityId();
    $group_type = $entity->getGroupType()->id();

    $cache_tags = [];
    if ($user_id and $group_type) {

      // Global options.
      $this->cacheBackend->delete('group_affiliation_options_by_user:' . $user_id);

      // Options by group type.
      $cache_tags[] = 'group_content_list:plugin:group_membership:entity:' . $user_id . ':group_type:' . $group_type;
      $this->cacheTagsInvalidator->invalidateTags($cache_tags);
    }
  }

  /**
   * Invalidate cache on group type change.
   *
   * Why this is needed:
   *  - If group type affiliation candidate status is changed (enabled or
   *    disabled).
   *  - If group type affiliation is changed (enabled or disabled).
   *  - If group type label is changed (sorting).
   *
   * Custom cache ids:
   *  - group_affiliation_options_by_user
   *  - group_affiliation_candidates
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   Group type entity.
   *
   * @return void
   *   Return void.
   */
  #[
    Hook('group_type_insert'),
    Hook('group_type_update'),
    Hook('group_type_delete')
  ]
  public function groupTypeChange(GroupTypeInterface $group_type): void {
    // Invalidate cache only for group types that are eligible for affiliation.
    $group_type_is_affiliation_candidate = $group_type->getThirdPartySetting('social_profile', GroupAffiliation::AFFILIATION_CANDIDATE_CONFIG_KEY);
    if ($group_type_is_affiliation_candidate) {
      // Invalidate general cache tag group_affiliation_options_by_user.
      $this->cacheTagsInvalidator->invalidateTags([
        GroupAffiliation::GENERAL_CACHE_TAG,
        'group_affiliation_candidates',
      ]);
    }
  }

  /**
   * Invalidate cache on group change.
   *
   * There is no need to invalidate cache on group create or delete, because
   * primarily this will be handled by membership create or delete.
   *
   * Why this is needed:
   *   - If the group label is changed the group affiliation options by user
   *     must be reordered (cache deleted).
   *   - If flexible groups visibility is changed (members only (secret) groups,
   *     can not be on the list).
   *   - If group status is changed (published/unpublished).
   *
   * Custom cache tags:
   *  - group_content_list:plugin:group_membership:entity:{$user_id}:group_type:{$group_type}
   *
   * Custom cache ids:
   *  - group_affiliation_options_by_user:{$user_id}
   *
   * @param \Drupal\group\Entity\Group $group
   *   Group entity.
   *
   * @return void
   *   Return void.
   */
  #[
    Hook('group_update')
  ]
  public function groupChange(Group $group): void {
    $group_types = $this->groupAffiliation->getAffiliationEnabledGroupTypes();
    if (!in_array($group->getGroupType()->id(), array_keys($group_types))) {

      // Early return if group type does not have affiliation enabled.
      return;
    }

    // If the group label is changed the group affiliation options by user must
    // be reordered (cache deleted). Cache is only deleted for users who are
    // members of the updated group. Because loadByGroup is well cached, it is
    // inexpensive to loop through all membership items. In case the loop below
    // would become a performance bottleneck, it is suggested to invalidate the
    // general "group_affiliation_options_by_user" cache tag instead.
    // @todo This cache invalidation should be ideally triggered only on label
    //   change, status change or flexible group visibility change.
    $memberships = $this->loadByGroup($group);
    /** @var \Drupal\group\Entity\GroupMembership $membership */
    foreach ($memberships as $membership) {
      $user_id = $membership->getEntityId();
      $group_type_id = $membership->getGroupType()->id();
      $this->cacheBackend->delete('group_affiliation_options_by_user:' . $user_id);
      $this->cacheTagsInvalidator->invalidateTags([
        'group_content_list:plugin:group_membership:entity:' . $user_id . ':group_type:' . $group_type_id,
      ]);
    }
  }

}

<?php

/**
 * @file
 * Contains \Drupal\group\Cache\Context\GroupMembershipPermissionsCacheContext.
 */

namespace Drupal\group\Cache\Context;

use Drupal\group\Entity\GroupRole;
use Drupal\group\Access\GroupPermissionsHashGeneratorInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;

/**
 * Defines a cache context for "per group membership permissions" caching.
 *
 * Cache context ID: 'group_membership.permissions'.
 */
class GroupMembershipPermissionsCacheContext extends GroupMembershipCacheContextBase implements CacheContextInterface {

  /**
   * The permissions hash generator.
   *
   * @var \Drupal\group\Access\GroupPermissionsHashGeneratorInterface
   */
  protected $permissionsHashGenerator;

  /**
   * Constructs a new GroupMembershipPermissionsCacheContext class.
   *
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $context_provider
   *   The group route context.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\group\Access\GroupPermissionsHashGeneratorInterface $hash_generator
   *   The permissions hash generator.
   */
  public function __construct(ContextProviderInterface $context_provider, AccountInterface $user, GroupPermissionsHashGeneratorInterface $hash_generator) {
    parent::__construct($context_provider, $user);
    $this->permissionsHashGenerator = $hash_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t("Group membership permissions");
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    // If there was no group on the route, there can be no membership.
    if (empty($this->group)) {
      return 'none';
    }

    return $this->permissionsHashGenerator->generate($this->group, $this->user);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    $cacheable_metadata = new CacheableMetadata();

    if (!empty($this->group)) {
      $tags = [];

      // If the user is a member, we need to add the membership's cache tag.
      if ($group_membership = $this->group->getMember($this->user)) {
        $tags = array_merge($tags, $group_membership->getGroupContent()->getCacheTags());
        $group_roles = $group_membership->getRoles();
      }
      else {
        $role_name = $this->user->id() == 0
          ? $this->group->bundle() . '-outsider'
          : $this->group->bundle() . '-anonymous';
        $group_roles[$role_name] = GroupRole::load($role_name);
      }

      // We also need to add the group roles' cache tags.
      foreach ($group_roles as $group_role) {
        $tags = array_merge($tags, $group_role->getCacheTags());
      }

      $cacheable_metadata->setCacheTags($tags);
    }

    return $cacheable_metadata;
  }

}

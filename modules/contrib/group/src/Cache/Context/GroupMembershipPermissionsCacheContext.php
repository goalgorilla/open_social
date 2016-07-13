<?php

/**
 * @file
 * Contains \Drupal\group\Cache\Context\GroupMembershipPermissionsCacheContext.
 */

namespace Drupal\group\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupPermissionsHashGeneratorInterface;

/**
 * Defines a cache context for "per group membership permissions" caching.
 *
 * Please note: This cache context uses the group from the current route as the
 * value object to work with. This context is therefore only to be used with
 * data that was based on the group from the route. You can retrieve it using
 * the 'entity:group' context provided by the 'group.group_route_context'
 * service. See an example at: \Drupal\group\Plugin\Block\GroupOperationsBlock.
 *
 * Cache context ID: 'group_membership.roles.permissions'.
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
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match object.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\Access\GroupPermissionsHashGeneratorInterface $hash_generator
   *   The permissions hash generator.
   */
  public function __construct(RouteMatchInterface $current_route_match, AccountInterface $user, EntityTypeManagerInterface $entity_type_manager, GroupPermissionsHashGeneratorInterface $hash_generator) {
    parent::__construct($current_route_match, $user, $entity_type_manager);
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
    // If there was no existing group on the route, there can be no membership.
    if (!$this->hasExistingGroup()) {
      return 'none';
    }

    return $this->permissionsHashGenerator->generate($this->group, $this->user);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    $cacheable_metadata = new CacheableMetadata();

    // If any of the membership's roles are updated, it could mean the list of
    // permissions changed as well. We therefore need to set the membership's
    // roles' cacheable metadata.
    //
    // Note that we do not set the membership's cacheable metadata because that
    // one is taken care of in the parent 'group_membership.roles' context.
    if ($this->hasExistingGroup()) {
      // Retrieve all of the group roles the user may get for the group.
      $group_roles = $this->groupRoleStorage()->loadByUserAndGroup($this->user, $this->group);

      // Merge the cacheable metadata of all the roles.
      foreach ($group_roles as $group_role) {
        $group_role_cacheable_metadata = new CacheableMetadata();
        $group_role_cacheable_metadata->createFromObject($group_role);
        $cacheable_metadata->merge($group_role_cacheable_metadata);
      }
    }

    return $cacheable_metadata;
  }

}

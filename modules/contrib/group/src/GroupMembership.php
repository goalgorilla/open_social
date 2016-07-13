<?php

/**
 * @file
 * Contains \Drupal\group\GroupMembership.
 */

namespace Drupal\group;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\group\Entity\GroupContentInterface;

/**
 * Wrapper class for a GroupContent entity representing a membership.
 * 
 * Should be loaded through the 'group.membership_loader' service.
 */
class GroupMembership implements CacheableDependencyInterface {

  /**
   * The group content entity to wrap.
   *
   * @var \Drupal\group\Entity\GroupContentInterface
   */
  protected $groupContent;

  /**
   * Constructs a new GroupMembership.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   The group content entity representing the membership.
   *
   * @throws \Exception
   *   Exception thrown when trying to instantiate this class with a
   *   GroupContent entity that was not based on the GroupMembership content
   *   enabler plugin.
   */
  public function __construct(GroupContentInterface $group_content) {
    if ($group_content->getGroupContentType()->getContentPluginId() == 'group_membership') {
      $this->groupContent = $group_content;
    }
    else {
      throw new \Exception('Trying to create a GroupMembership from an incompatible GroupContent entity.');
    }
  }

  /**
   * Returns the fieldable GroupContent entity for the membership.
   *
   * @return \Drupal\group\Entity\GroupContentInterface
   */
  public function getGroupContent() {
    return $this->groupContent;
  }

  /**
   * Returns the group for the membership.
   *
   * @return \Drupal\group\Entity\GroupInterface
   */
  public function getGroup() {
    return $this->groupContent->getGroup();
  }

  /**
   * Returns the user for the membership.
   *
   * @return \Drupal\Core\Session\AccountInterface
   */
  public function getUser() {
    return $this->groupContent->getEntity();
  }

  /**
   * Returns the group roles for the membership.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface[]
   *   An array of group roles, keyed by their ID.
   */
  public function getRoles() {
    /** @var \Drupal\group\Entity\Storage\GroupRoleStorageInterface $group_role_storage */
    $group_role_storage = \Drupal::entityTypeManager()->getStorage('group_role');
    return $group_role_storage->loadByUserAndGroup($this->getUser(), $this->getGroup());
  }

  /**
   * Checks whether the member has a permission.
   *
   * @param string $permission
   *   The permission to check for.
   *
   * @return bool
   *   Whether the member has the requested permission.
   */
  public function hasPermission($permission) {
    foreach ($this->getRoles() as $group_role) {
      if ($group_role->hasPermission($permission)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return $this->getGroupContent()->getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->getGroupContent()->getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->getGroupContent()->getCacheMaxAge();
  }

}

<?php

/**
 * @file
 * Contains \Drupal\group\GroupMembership.
 */

namespace Drupal\group;

use Drupal\group\Entity\GroupRole;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Wrapper class for a GroupContent entity representing a membership.
 */
class GroupMembership {

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
   * Loads a membership by group and user.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to load the membership from.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user to load the membership for.
   *
   * @return \Drupal\group\GroupMembership|false
   *   The loaded GroupMembership or FALSE if none was found.
   */
  public static function load(GroupInterface $group, AccountInterface $account) {
    if ($group_content = $group->getContent('group_membership', ['entity_id' => $account->id()])) {
      return new static(reset($group_content));
    }
    return FALSE;
  }

  /**
   * Loads all memberships for a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to load the memberships from.
   * @param string|array $roles
   *   (optional) A group role machine name or a list of group role machine
   *   names to filter on. Valid results only need to match on one role.
   *
   * @return \Drupal\group\GroupMembership[]
   *   The loaded GroupMemberships matching the criteria.
   */
  public static function loadByGroup(GroupInterface $group, $roles = NULL) {
    $entity_manager = \Drupal::entityTypeManager();

    // Retrieve the group content type ID for the provided group's type.
    $plugin = $group->getGroupType()->getContentPlugin('group_membership');
    $group_content_type_id = $plugin->getContentTypeConfigId();

    // Try to load all possible membership group content for the group.
    $properties = ['type' => $group_content_type_id, 'gid' => $group->id()];
    if (!empty($roles)) {
      $properties['group_roles'] = (array) $roles;
    }

    $group_contents = $entity_manager
      ->getStorage('group_content')
      ->loadByProperties($properties);

    // Wrap the retrieved group content in a GroupMembership.
    $group_memberships = [];
    foreach ($group_contents as $group_content) {
      $group_memberships[] = new static($group_content);
    }

    return $group_memberships;
  }

  /**
   * Loads all memberships for a user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user to load the membership for. Leave blank to load the
   *   memberships of the currently logged in user.
   * @param string|array $roles
   *   (optional) A group role machine name or a list of group role machine
   *   names to filter on. Valid results only need to match on one role.
   *
   * @return \Drupal\group\GroupMembership[]
   *   The loaded GroupMemberships matching the criteria.
   */
  public static function loadByUser(AccountInterface $account = NULL, $roles = NULL) {
    $entity_manager = \Drupal::entityTypeManager();
    if (!isset($account)) {
      $account = \Drupal::currentUser();
    }

    // Load all group content types for the membership content enabler plugin.
    $group_content_types = $entity_manager
      ->getStorage('group_content_type')
      ->loadByProperties(['content_plugin' => 'group_membership']);

    // If none were found, there can be no memberships either.
    if (empty($group_content_types)) {
      return [];
    }

    // Try to load all possible membership group content for the user.
    $group_content_type_ids = [];
    foreach ($group_content_types as $group_content_type) {
      $group_content_type_ids[] = $group_content_type->id();
    }

    $properties = ['type' => $group_content_type_ids, 'entity_id' => $account->id()];
    if (!empty($roles)) {
      $properties['group_roles'] = (array) $roles;
    }

    $group_contents = $entity_manager
      ->getStorage('group_content')
      ->loadByProperties($properties);

    // Wrap the retrieved group content in a GroupMembership.
    $group_memberships = [];
    foreach ($group_contents as $group_content) {
      $group_memberships[] = new static($group_content);
    }

    return $group_memberships;
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
    $roles = [];

    // Retrieve all group roles for the membership.
    foreach ($this->groupContent->group_roles->referencedEntities() as $group_role) {
      $roles[$group_role->id()] = $group_role;
    }

    // Add the special 'member' role to the retrieved roles.
    $member_role_id = $this->getGroup()->bundle() . '-member';
    $roles[$member_role_id] = GroupRole::load($member_role_id);

    return $roles;
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

}

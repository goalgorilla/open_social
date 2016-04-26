<?php

/**
 * @file
 * Contains \Drupal\group\Entity\GroupRoleInterface.
 */

namespace Drupal\group\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a group role entity.
 */
interface GroupRoleInterface extends ConfigEntityInterface {

  /**
   * Returns the weight.
   *
   * @return int
   *   The weight of this role.
   */
  public function getWeight();

  /**
   * Sets the weight to the given value.
   *
   * @param int $weight
   *   The desired weight.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface
   *   The group role this was called on.
   */
  public function setWeight($weight);

  /**
   * Returns whether the role is tied to a group type.
   *
   * @return bool
   *   Whether the role is tied to a group type.
   */
  public function isInternal();

  /**
   * Returns whether the role is for an anonymous user.
   *
   * @return bool
   *   Whether the role is for an anonymous user.
   */
  public function isAnonymous();

  /**
   * Returns whether the role is for an outsider.
   *
   * @return bool
   *   Whether the role is for an outsider.
   */
  public function isOutsider();

  /**
   * Returns whether the role is for a member.
   *
   * @return bool
   *   Whether the role is for a member.
   */
  public function isMember();

  /**
   * Returns the group type this role belongs to.
   *
   * @return \Drupal\group\Entity\GroupTypeInterface
   *   The group type this role belongs to.
   */
  public function getGroupType();

  /**
   * Returns the ID of the group type this role belongs to.
   *
   * @return string
   *   The ID of the group type this role belongs to.
   */
  public function getGroupTypeId();

  /**
   * Returns a list of permissions assigned to the role.
   *
   * @return array
   *   The permissions assigned to the role.
   */
  public function getPermissions();

  /**
   * Checks if the role has a permission.
   *
   * @param string $permission
   *   The permission to check for.
   *
   * @return bool
   *   TRUE if the role has the permission, FALSE if not.
   */
  public function hasPermission($permission);

  /**
   * Grants a permission to the role.
   *
   * @param string $permission
   *   The permission to grant.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface
   *   The group role this was called on.
   */
  public function grantPermission($permission);

  /**
   * Grants multiple permission to the role.
   *
   * @param string[] $permissions
   *   The permissions to grant.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface
   *   The group role this was called on.
   */
  public function grantPermissions($permissions);

  /**
   * Revokes a permission from the role.
   *
   * @param string $permission
   *   The permission to revoke.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface
   *   The group role this was called on.
   */
  public function revokePermission($permission);

  /**
   * Revokes multiple permissions from the role.
   *
   * @param string[] $permissions
   *   The permissions to revoke.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface
   *   The group role this was called on.
   */
  public function revokePermissions($permissions);

  /**
   * Changes permissions for the role.
   *
   * This function may be used to grant and revoke multiple permissions at once.
   * For example, when a form exposes checkboxes to configure permissions for a
   * role, the form submit handler may directly pass the submitted values for the
   * checkboxes form element to this function.
   *
   * @param array $permissions
   *   (optional) An associative array, where the key holds the permission name
   *   and the value determines whether to grant or revoke that permission. Any
   *   value that evaluates to TRUE will cause the permission to be granted.
   *   Any value that evaluates to FALSE will cause the permission to be
   *   revoked.
   *   @code
   *     [
   *       'administer group' => 0,         // Revoke 'administer group'
   *       'edit group' => FALSE,           // Revoke 'edit group'
   *       'administer members' => 1,       // Grant 'administer members'
   *       'leave group' => TRUE,           // Grant 'leave group'
   *       'join group' => 'join group',    // Grant 'join group'
   *     ]
   *   @endcode
   *   Existing permissions are not changed, unless specified in $permissions.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface
   *   The group role this was called on.
   */
  public function changePermissions(array $permissions = []);

}

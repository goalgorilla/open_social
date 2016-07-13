<?php

/**
 * @file
 * Contains \Drupal\group\Entity\GroupRole.
 *
 * @todo Other edit/delete paths, perhaps use a route provider?
 */

namespace Drupal\group\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Group role configuration entity.
 *
 * @ConfigEntityType(
 *   id = "group_role",
 *   label = @Translation("Group role"),
 *   handlers = {
 *     "storage" = "Drupal\group\Entity\Storage\GroupRoleStorage",
 *     "access" = "Drupal\group\Entity\Access\GroupRoleAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\group\Entity\Form\GroupRoleForm",
 *       "edit" = "Drupal\group\Entity\Form\GroupRoleForm",
 *       "delete" = "Drupal\group\Entity\Form\GroupRoleDeleteForm"
 *     },
 *     "list_builder" = "Drupal\group\Entity\Controller\GroupRoleListBuilder",
 *   },
 *   admin_permission = "administer group",
 *   config_prefix = "role",
 *   static_cache = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "weight" = "weight",
 *     "label" = "label"
 *   },
 *   links = {
 *     "collection" = "/admin/group/types/manage/{group_type}/roles",
 *     "edit-form" = "/admin/group/types/manage/{group_type}/roles/{group_role}",
 *     "delete-form" = "/admin/group/types/manage/{group_type}/roles/{group_role}/delete",
 *     "permissions-form" = "/admin/group/types/manage/{group_type}/roles/{group_role}/permissions"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "weight",
 *     "internal",
 *     "group_type",
 *     "permissions"
 *   }
 * )
 */
class GroupRole extends ConfigEntityBase implements GroupRoleInterface {

  /**
   * The machine name of the group role.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the group role.
   *
   * @var string
   */
  protected $label;

  /**
   * The weight of the group role in administrative listings.
   *
   * @var int
   */
  protected $weight;

  /**
   * Whether the group role is used internally.
   *
   * Examples of these are the special group roles 'anonymous', 'outsider' and
   * 'member'.
   *
   * @var bool
   */
  protected $internal = FALSE;

  /**
   * The ID of the group type this role belongs to.
   *
   * @var string
   */
  protected $group_type;

  /**
   * The permissions belonging to the group role.
   *
   * @var string[]
   */
  protected $permissions = [];

  /**
   * The part of the group role ID after the period.
   *
   * @var string
   */
  protected $strippedId;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * Returns just the part of the ID pertaining to the group role.
   *
   * @return string
   *   The part of the group role ID after the period.
   */
  protected function strippedId() {
    if (!isset($this->strippedId)) {
      list(, $group_role) = explode('-', $this->id(), 2);
      $this->strippedId = $group_role;
    }
    return $this->strippedId;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->get('weight');
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->set('weight', $weight);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return $this->internal;
  }

  /**
   * {@inheritdoc}
   */
  public function isAnonymous() {
    return $this->strippedId() == 'anonymous';
  }

  /**
   * {@inheritdoc}
   */
  public function isOutsider() {
    return $this->strippedId() == 'outsider';
  }

  /**
   * {@inheritdoc}
   */
  public function isMember() {
    return !$this->isAnonymous() && !$this->isOutsider();
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupType() {
    return GroupType::load($this->group_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupTypeId() {
    return $this->group_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    return $this->permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission($permission) {
    return in_array($permission, $this->permissions);
  }

  /**
   * {@inheritdoc}
   */
  public function grantPermission($permission) {
    return $this->grantPermissions([$permission]);
  }

  /**
   * {@inheritdoc}
   */
  public function grantPermissions($permissions) {
    $this->permissions = array_unique(array_merge($this->permissions, $permissions));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function revokePermission($permission) {
    return $this->revokePermissions([$permission]);
  }

  /**
   * {@inheritdoc}
   */
  public function revokePermissions($permissions) {
    $this->permissions = array_diff($this->permissions, $permissions);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function changePermissions(array $permissions = []) {
    // Grant new permissions to the role.
    $grant = array_filter($permissions);
    if (!empty($grant)) {
      $this->grantPermissions(array_keys($grant));
    }

    // Revoke permissions from the role.
    $revoke = array_diff_assoc($permissions, $grant);
    if (!empty($revoke)) {
      $this->revokePermissions(array_keys($revoke));
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    $uri_route_parameters['group_type'] = $this->getGroupTypeId();
    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $this->addDependency('config', $this->getGroupType()->getConfigDependencyName());
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    parent::postLoad($storage, $entities);
    // Sort the queried roles by their weight.
    // See \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
    uasort($entities, 'static::sort');
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if (!isset($this->weight) && ($group_roles = $storage->loadMultiple())) {
      // Set a role weight to make this new role last.
      $max = array_reduce($group_roles, function($max, $group_role) {
        return $max > $group_role->weight ? $max : $group_role->weight;
      });

      $this->weight = $max + 1;
    }
  }

}

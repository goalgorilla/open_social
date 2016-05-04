<?php

/**
 * @file
 * Contains \Drupal\group\Entity\Controller\GroupRoleController.
 */

namespace Drupal\group\Entity\Controller;

use Drupal\group\Entity\GroupRole;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for GroupRole routes.
 */
class GroupRoleController extends ControllerBase {

  /**
   * Provides the group role submission form.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type to add the group role to.
   *
   * @return array
   *   A group role submission form.
   */
  public function add(GroupTypeInterface $group_type) {
    $group_role = GroupRole::create(['group_type' => $group_type->id()]);
    $form = $this->entityFormBuilder()->getForm($group_role, 'add');
    return $form;
  }

  /**
   * The _title_callback for the entity.group_role.add_form route.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type to base the title on.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle(GroupTypeInterface $group_type) {
    return $this->t('Create group role for @name', ['@name' => $group_type->label()]);
  }

}

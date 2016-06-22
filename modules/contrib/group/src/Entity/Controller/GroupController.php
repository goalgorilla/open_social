<?php

/**
 * @file
 * Contains \Drupal\group\Entity\Controller\GroupController.
 */

namespace Drupal\group\Entity\Controller;

use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupType;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Group routes.
 */
class GroupController extends ControllerBase {

  /**
   * Displays add content links for available group types.
   *
   * Redirects to group/add/[type] if only one group type is available.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the group types that can be added. However,
   *   if there is only one group type available to the user, the function will
   *   return a RedirectResponse to the group add page for that group type.
   */
  public function addPage() {
    $group_types = GroupType::loadMultiple();

    // Only use group types the user has access to.
    foreach (array_keys($group_types) as $group_type_id) {
      if (!$this->entityTypeManager()->getAccessControlHandler('group')->createAccess($group_type_id)) {
        unset($group_types[$group_type_id]);
      }
    }

    // Bypass the page if only one group type is available.
    if (count($group_types) == 1) {
      $group_type = array_shift($group_types);
      return $this->redirect('entity.group.add_form', ['group_type' => $group_type->id()]);
    }

    return [
      '#theme' => 'group_add_list',
      '#group_types' => $group_types,
    ];
  }

  /**
   * Provides the group submission form.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type of the group to add.
   *
   * @return array
   *   A group submission form.
   */
  public function add(GroupTypeInterface $group_type) {
    $group = Group::create(['type' => $group_type->id()]);
    $form = $this->entityFormBuilder()->getForm($group, 'add');
    return $form;
  }

  /**
   * The _title_callback for the entity.group.add_form route.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type to base the title on.
   *
   * @return string
   *   The page title.
   */
  public function addTitle(GroupTypeInterface $group_type) {
    return $this->t('Create @name', ['@name' => $group_type->label()]);
  }

}

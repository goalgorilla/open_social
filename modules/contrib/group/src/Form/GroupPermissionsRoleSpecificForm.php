<?php

/**
 * @file
 * Contains \Drupal\group\Form\GroupPermissionsRoleSpecificForm.
 */

namespace Drupal\group\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupRoleInterface;

/**
 * Provides the user permissions administration form for a specific group role.
 */
class GroupPermissionsRoleSpecificForm extends GroupPermissionsForm {

  /**
   * The specific group role for this form.
   *
   * @var \Drupal\group\Entity\GroupRoleInterface
   */
  protected $groupRole;

  /**
   * {@inheritdoc}
   */
  protected function getType() {
    return $this->groupRole->getGroupType();
  }

  /**
   * {@inheritdoc}
   */
  protected function getRoles() {
    return [$this->groupRole->id() => $this->groupRole];
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\group\Entity\GroupRoleInterface $group_role
   *   The group role used for this form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, GroupRoleInterface $group_role = NULL) {
    if ($group_role->isInternal()) {
      return ['#markup' => t('Cannot edit an internal group role directly.')];
    }

    $this->groupRole = $group_role;
    return parent::buildForm($form, $form_state);
  }

}

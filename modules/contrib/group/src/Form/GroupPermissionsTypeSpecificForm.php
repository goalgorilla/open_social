<?php

/**
 * @file
 * Contains \Drupal\group\Form\GroupPermissionsTypeSpecificForm.
 */

namespace Drupal\group\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupTypeInterface;

/**
 * Provides the user permissions administration form for a specific group type.
 */
class GroupPermissionsTypeSpecificForm extends GroupPermissionsForm {

  /**
   * The specific group role for this form.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupType;

  /**
   * {@inheritdoc}
   */
  protected function getInfo() {
    $list = [
      'role_info' => [
        '#prefix' => '<p>' . $this->t('Group types use three special roles:') . '</p>',
        '#theme' => 'item_list',
        '#items' => [
          ['#markup' => $this->t('<strong>Anonymous:</strong> This is the same as the global Anonymous role, meaning the user has no account.')],
          ['#markup' => $this->t('<strong>Outsider:</strong> This means the user has an account on the site, but is not a member of the group.')],
          ['#markup' => $this->t('<strong>Member:</strong> The default role for anyone in the group. Behaves like the "Authenticated user" role does globally.')],
        ],
      ],
    ];

    return $list + parent::getInfo();
  }

  /**
   * {@inheritdoc}
   */
  protected function getType() {
    return $this->groupType;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRoles() {
    return $this->groupType->getRoles();
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type used for this form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, GroupTypeInterface $group_type = NULL) {
    $this->groupType = $group_type;
    return parent::buildForm($form, $form_state);
  }

}

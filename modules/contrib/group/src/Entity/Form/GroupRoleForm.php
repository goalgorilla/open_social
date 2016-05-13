<?php

/**
 * @file
 * Contains \Drupal\group\Entity\Form\GroupRoleForm.
 */

namespace Drupal\group\Entity\Form;

use Drupal\group\Entity\GroupRole;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for group role forms.
 */
class GroupRoleForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\group\Entity\GroupRoleInterface $group_role */
    $form = parent::form($form, $form_state);
    $group_role = $this->entity;
    $group_role_id = '';

    if ($group_role->isInternal()) {
      return [
        '#title' => t('Error'),
        'description' => ['#markup' => '<p>' . t('Cannot edit an internal group role directly.') . '</p>'],
      ];
    }

    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add group role');
    }
    else {
      $form['#title'] = $this->t('Edit %label group role', ['%label' => $group_role->label()]);
    }

    $form['label'] = [
      '#title' => t('Name'),
      '#type' => 'textfield',
      '#default_value' => $group_role->label(),
      '#description' => t('The human-readable name of this group role. This text will be displayed on the group permissions page.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    // Since group role IDs are prefixed by the group type's ID followed by a
    // period, we need to save some space for that.
    $subtract = strlen($group_role->getGroupTypeId()) + 1;

    // Since machine names with periods in it are technically not allowed, we
    // strip the group type ID prefix when editing a group role.
    if ($group_role->id()) {
      list(, $group_role_id) = explode('-', $group_role->id(), 2);
    }

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $group_role_id,
      '#maxlength' => EntityTypeInterface::ID_MAX_LENGTH - $subtract,
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'source' => ['label'],
      ],
      '#description' => t('A unique machine-readable name for this group role. It must only contain lowercase letters, numbers, and underscores.'),
      '#disabled' => !$group_role->isNew(),
      '#field_prefix' => $group_role->getGroupTypeId() . '-',
    ];

    $form['weight'] = [
      '#type' => 'value',
      '#value' => $group_role->getWeight(),
    ];
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    // Do not show action buttons for an internal group role.
    if ($this->entity->isInternal()) {
      return [];
    }

    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = t('Save group role');
    $actions['delete']['#value'] = t('Delete group role');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $id = trim($form_state->getValue('id'));
    // '0' is invalid, since elsewhere we might check it using empty().
    if ($id == '0') {
      $form_state->setErrorByName('id', $this->t("Invalid machine-readable name. Enter a name other than %invalid.", ['%invalid' => $id]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\group\Entity\GroupRoleInterface $group_role */
    $group_role = $this->entity;
    $group_role->set('id', $group_role->getGroupTypeId() . '-' . $group_role->id());
    $group_role->set('label', trim($group_role->label()));

    $status = $group_role->save();
    $t_args = ['%label' => $group_role->label()];

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('The group role %label has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message(t('The group role %label has been added.', $t_args));

      $context = array_merge($t_args, ['link' => $group_role->toLink($this->t('View'), 'collection')->toString()]);
      $this->logger('group')->notice('Added group role %label.', $context);
    }

    $form_state->setRedirectUrl($group_role->toUrl('collection'));
  }

  /**
   * Checks whether a group role ID exists already.
   *
   * @param string $id
   *
   * @return bool
   *   Whether the ID is taken.
   */
  public function exists($id) {
    /** @var \Drupal\group\Entity\GroupRoleInterface $group_role */
    $group_role = $this->entity;
    return (boolean) GroupRole::load($group_role->getGroupTypeId() . '-' .$id);
  }

}

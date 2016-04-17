<?php

/**
 * @file
 * Contains \Drupal\profile\Form\ProfileTypeForm.
 */

namespace Drupal\profile\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field_ui\FieldUI;
use Drupal\user\Entity\Role;


/**
 * Form controller for profile type forms.
 */
class ProfileTypeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $type = $this->entity;

    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add profile type');
    }
    else {
      $form['#title'] = $this->t('Edit %label profile type', ['%label' => $type->label()]);
    }

    $form['label'] = [
      '#title' => t('Label'),
      '#type' => 'textfield',
      '#default_value' => $type->label(),
      '#description' => t('The human-readable name of this profile type.'),
      '#required' => TRUE,
      '#size' => 30,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => '\Drupal\profile\Entity\ProfileType::load',
        'source' => ['label'],
      ],
    ];
    $form['registration'] = [
      '#type' => 'checkbox',
      '#title' => t('Include in user registration form'),
      '#default_value' => $type->getRegistration(),
    ];
    $form['multiple'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow multiple profiles'),
      '#default_value' => $type->getMultiple(),
    ];

    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => t('Allowed roles'),
      '#description' => $this->t('Limit the users that can have this profile by role.</br><em>None will indicate that all users can have this profile type.</em>'),
      '#options' => [],
      '#default_value' => $type->getRoles(),
    ];
    foreach (Role::loadMultiple() as $role) {
      /** @var \Drupal\user\Entity\Role $role */
      // We aren't interested in anon role.
      if ($role->id() !== Role::ANONYMOUS_ID) {
        $form['roles']['#options'][$role->id()] = $role->label();
      }
    }

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    if (\Drupal::moduleHandler()->moduleExists('field_ui') &&
      $this->getEntity()->isNew()
    ) {
      $actions['save_continue'] = $actions['submit'];
      $actions['save_continue']['#value'] = t('Save and manage fields');
      $actions['save_continue']['#submit'][] = [$this, 'redirectToFieldUI'];
    }
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $type = $this->entity;
    $status = $type->save();

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('%label profile type has been updated.', ['%label' => $type->label()]));
    }
    else {
      drupal_set_message(t('%label profile type has been created.', ['%label' => $type->label()]));
    }
    $form_state->setRedirect('entity.profile_type.collection');
  }

  /**
   * Form submission handler to redirect to Manage fields page of Field UI.
   */
  public function redirectToFieldUI(array $form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()['#parents'][0] === 'save_continue' && $route_info = FieldUI::getOverviewRouteInfo('profile', $this->entity->id())) {
      $form_state->setRedirectUrl($route_info);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.profile_type.delete_form', [
      'profile_type' => $this->entity->id(),
    ]);
  }

}

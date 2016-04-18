<?php

/**
 * @file
 * Contains \Drupal\profile\Form\ProfileForm.
 */

namespace Drupal\profile\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\profile\Entity\ProfileType;

/**
 * Form controller for profile forms.
 */
class ProfileForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $entity = parent::buildEntity($form, $form_state);
    if ($entity->isNew()) {
      $entity->setCreatedTime(REQUEST_TIME);
    }
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $this->entity;

    // Add an "Activate" button.
    $element['set_default'] = $element['submit'];
    $element['set_default']['#value'] = t('Save and make default');
    $element['set_default']['#weight'] = 10;
    $element['set_default']['#access'] = !$profile->isDefault();
    array_unshift($element['set_default']['#submit'], [$this, 'setDefault']);

    return $element;
  }

  /**
   * Form submission handler for the 'set_default' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A reference to a keyed array containing the current state of the form.
   */
  public function setDefault(array $form, FormStateInterface $form_state) {
    $form_state->setValue('is_default', TRUE);
  }

  /**
   * Form submission handler for the 'deactivate' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A reference to a keyed array containing the current state of the form.
   */
  public function deactivate(array $form, FormStateInterface $form_state) {
    $form_state->setValue('status', FALSE);
    $form_state->setValue('is_default', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $profile_type = ProfileType::load($this->entity->bundle());

    switch ($this->entity->save()) {
      case SAVED_NEW:
        drupal_set_message($this->t('%label profile has been created.', ['%label' => $profile_type->label()]));
        break;

      case SAVED_UPDATED:
        drupal_set_message($this->t('%label profile has been updated.', ['%label' => $profile_type->label()]));
        break;
    }

    $form_state->setRedirect('entity.user.canonical', [
      'user' => $this->entity->getOwnerId(),
    ]);
  }

}

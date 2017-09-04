<?php

namespace Drupal\entity_access_by_field\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EntityVisibilityForm.
 *
 * @package Drupal\entity_access_by_field\Form
 */
class EntityVisibilityForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'entity_access_by_field.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_access_by_field_visibility_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get config for this form.
    $config = $this->config('entity_access_by_field.settings');

    $form['disable_public_visibility'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable public visibility'),
      '#description' => $this->t('Turning on this feature makes sure regular user can no longer post public content anywhere on the platform.'),
      '#default_value' => $config->get('disable_public_visibility'),
    ];

    $form['default_visibility'] = [
      '#type' => 'select',
      '#title' => $this->t('Default visibility'),
      '#default_value' => $config->get('default_visibility'),
      '#options' => [
        'public' => $this->t('Public - visible to everyone including people who are not a member'),
        'community' => $this->t('Community - visible only to logged in members'),
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $this
      ->config('entity_access_by_field.settings')
      ->setData($form_state->getValues())
      ->save();

    parent::submitForm($form, $form_state);
  }

}

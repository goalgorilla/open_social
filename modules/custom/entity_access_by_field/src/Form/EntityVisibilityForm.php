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

    // When we add the disable public visibility,
    // it will also affect the group visibility settings.
    if ($form_state->getValue('disable_public_visibility') === (int) TRUE
      && \Drupal::service('module_handler')->moduleExists('social_group_flexible_group')) {
      // Get the current visibility configuration for groups.
      $config = \Drupal::service('config.factory')->getEditable('social_group.settings');
      $visibilities = $config->get('available_visibility_options');
      // Disable the visibility and save.
      $visibilities['public'] = FALSE;
      $config->set('available_visibility_options', $visibilities);
      $config->save();
    }

    parent::submitForm($form, $form_state);
  }

}

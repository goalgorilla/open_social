<?php

/**
 * @file
 * Contains \Drupal\better_formats\Form\SettingsForm.
 */

namespace Drupal\better_formats\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\better_formats\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'better_formats_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['better_formats.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('better_formats.settings');

    $form['control'] = [
      '#type' => 'fieldset',
      '#title' => t('Control'),
    ];

    $form['control']['per_field_core'] = [
      '#type'  => 'checkbox',
      '#title' => t('Use field default'),
      '#description' => t('Use the core field module default value to set the default format. This will force the default format even when the default field value is empty. To set a default format you must re-edit a text field after saving it with the "Filtered text" option turned on.'),
      '#default_value' => $config->get('per_field_core'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('better_formats.settings');
    $form_state->cleanValues();

    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}

<?php

namespace Drupal\social_event_max_enroll\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EventMaxEnrollSettingsForm.
 *
 * @package Drupal\social_event_max_enroll\Form
 */
class EventMaxEnrollSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_max_enroll_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['social_event_max_enroll.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $event_max_enroll_config = $this->config('social_event_max_enroll.settings');

    $form['max_enroll'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable maximum number of event enrollments'),
      '#description' => $this->t('Enabling this feature provides event organisers with the possibility to set a limit for event enrollments.'),
      '#default_value' => $event_max_enroll_config->get('max_enroll'),
    ];

    $form['max_enroll_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Maximum event enrollments field is required'),
      '#default_value' => $event_max_enroll_config->get('max_enroll_required'),
      '#states' => [
        'visible' => [
          'input[name="max_enroll"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('social_event_max_enroll.settings')
      ->set('max_enroll', $form_state->getValue('max_enroll'))
      ->set('max_enroll_required', $form_state->getValue('max_enroll_required'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}

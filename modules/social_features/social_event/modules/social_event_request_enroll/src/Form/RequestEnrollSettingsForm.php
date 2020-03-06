<?php

namespace Drupal\social_event_request_enroll\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class RequestEnrollSettingsForm.
 *
 * @package Drupal\social_event_request_enroll\Form
 */
class RequestEnrollSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_event_request_enroll_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['social_event_request_enroll.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $event_request_enroll_config = $this->config('social_event_request_enroll.settings');

    $form['request_enroll'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable requesting enrollment to events'),
      '#description' => $this->t('Enabling this feature provides the possibility to let users submit a request to enroll to an event.'),
      '#default_value' => $event_request_enroll_config->get('request_enroll'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('social_event_request_enroll.settings')
      ->set('request_enroll', $form_state->getValue('request_enroll'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}

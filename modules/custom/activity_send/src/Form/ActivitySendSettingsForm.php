<?php

namespace Drupal\activity_send\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure activity send settings.
 */
class ActivitySendSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'activity_send_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['activity_send.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('activity_send.settings');

    $form['activity_send_offline_control'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Offline Control Settings'),
      '#open' => FALSE,
    );
    $form['activity_send_offline_control']['activity_send_offline_window'] = array(
      '#type' => 'number',
      '#title' => $this->t('Offline window'),
      '#size' => 10,
      '#default_value' => $config->get('activity_send_offline_window'),
      '#min' => 0,
      '#description' => $this->t('Number of seconds of inactivity after which we assume user is offline.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('activity_send.settings');
    $config->set('activity_send_offline_window', $form_state->getValue('activity_send_offline_window'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}

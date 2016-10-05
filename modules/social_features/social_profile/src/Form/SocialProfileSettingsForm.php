<?php

namespace Drupal\social_profile\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure social profile settings.
 */
class SocialProfileSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_profile_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['social_profile.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_profile.settings');

    $form['privacy'] = array(
      '#type' => 'details',
      '#title' => $this->t('Privacy settings'),
      '#open' => TRUE,
    );
    $form['privacy']['social_profile_show_email'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show Email on user profiles'),
      '#default_value' => $config->get('social_profile_show_email'),
      '#description' => $this->t('Users with permission "view profile email" will be able to see email in any case.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('social_profile.settings');
    $config->set('social_profile_show_email', $form_state->getValue('social_profile_show_email'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}

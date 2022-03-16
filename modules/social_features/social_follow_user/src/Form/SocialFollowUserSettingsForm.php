<?php

namespace Drupal\social_follow_user\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form to configure Social Follow User settings.
 *
 * @package Drupal\social_follow_user\Form
 */
class SocialFollowUserSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['social_follow_user.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'social_follow_user_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Active'),
      '#default_value' => $this->config('social_follow_user.settings')->get('status'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $this->config('social_follow_user.settings')
      ->set('status', $form_state->getValue('status'))
      ->save();
  }

}

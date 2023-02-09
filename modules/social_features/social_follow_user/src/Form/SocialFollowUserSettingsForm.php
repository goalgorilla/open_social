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

    $follow_status = $this->config('social_follow_user.settings')->get('status');
    $roles = $this->config('social_follow_user.settings')->get('roles');
    $permission = 'flag follow_user';

    // Remove the permissions for following users if disabled.
    if ($follow_status === TRUE && $form_state->getValue('status') === 0) {
      // Permission can be different from default so retrieve it dynamically.
      $roles = user_role_names(FALSE, $permission);
      $roles = array_keys($roles);

      foreach ($roles as $role) {
        user_role_revoke_permissions($role, [$permission]);
      }
    }
    elseif ($follow_status === FALSE && $form_state->getValue('status') === 1) {
      // If the config is not set yet, then it means we have the default.
      if ($roles !== NULL) {
        // Add the permission to follow users if the feature is turned on.
        foreach ($roles as $role) {
          user_role_grant_permissions($role, [$permission]);
        }
      }
    }

    $this->config('social_follow_user.settings')
      ->set('status', $form_state->getValue('status'))
      ->set('roles', $roles)
      ->save();
  }

}

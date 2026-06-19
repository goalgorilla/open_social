<?php

declare(strict_types=1);

namespace Drupal\social_analytics\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure individual metrics preference platform settings.
 */
final class SocialAnalyticsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'social_analytics_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['social_analytics.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('social_analytics.settings');

    $form['individual_metrics'] = [
      '#type' => 'details',
      '#title' => $this->t('Individual metrics preferences'),
      '#open' => TRUE,
    ];

    $form['individual_metrics']['individual_metrics_preference_visibility_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to manage individual metrics visibility'),
      '#description' => $this->t('When enabled, users can choose whether their name appears in individual analytics reports and rankings.'),
      '#default_value' => $config->get('individual_metrics_preference_visibility_enabled'),
    ];

    $form['individual_metrics']['individual_metrics_show_by_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show user names in individual metrics by default'),
      '#description' => $this->t('When enabled, users are included in individual metrics by default unless they opt out. Users with a saved preference keep their last saved value.'),
      '#default_value' => $config->get('individual_metrics_show_by_default'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('social_analytics.settings')
      ->set(
        'individual_metrics_preference_visibility_enabled',
        (bool) $form_state->getValue('individual_metrics_preference_visibility_enabled')
      )
      ->set(
        'individual_metrics_show_by_default',
        (bool) $form_state->getValue('individual_metrics_show_by_default')
      )
      ->save();

    parent::submitForm($form, $form_state);
  }

}

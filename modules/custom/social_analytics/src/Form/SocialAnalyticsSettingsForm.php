<?php

declare(strict_types=1);

namespace Drupal\social_analytics\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\social_analytics\AnalyticsSettingsEdaHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure individual metrics preference platform settings.
 */
final class SocialAnalyticsSettingsForm extends ConfigFormBase {

  public function __construct(
    ConfigFactoryInterface $config_factory,
    ?TypedConfigManagerInterface $typed_config_manager,
    private readonly AnalyticsSettingsEdaHandler $analyticsSettingsEdaHandler,
  ) {
    parent::__construct($config_factory, $typed_config_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('social_analytics.analytics_settings_eda_handler'),
    );
  }

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
    $config = $this->config('social_analytics.settings');
    $previous_show_by_default = (bool) $config->get('individual_metrics_show_by_default');
    $show_by_default = (bool) $form_state->getValue('individual_metrics_show_by_default');

    $config
      ->set(
        'individual_metrics_preference_visibility_enabled',
        (bool) $form_state->getValue('individual_metrics_preference_visibility_enabled')
      )
      ->set('individual_metrics_show_by_default', $show_by_default)
      ->save();

    if ($previous_show_by_default !== $show_by_default) {
      $this->analyticsSettingsEdaHandler->dispatchDefaultChange($show_by_default);
    }

    parent::submitForm($form, $form_state);
  }

}

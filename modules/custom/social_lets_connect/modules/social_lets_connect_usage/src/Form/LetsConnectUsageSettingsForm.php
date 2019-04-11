<?php

namespace Drupal\social_lets_connect_usage\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Lets Connect Usage.
 */
class LetsConnectUsageSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_lets_connect_usage_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['social_lets_connect_usage.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['why'] = [
      '#type' => 'item',
      '#markup' => $this->t('Open Social would like to collect non-personal data to improve the product and your experience. We will never collect any personal data. You can choose which data you want to share with the Open Social team below (or none at all). The usage data is sent every 24 hours to the team.'),
    ];
    $form['usage_data_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Share usage data with Open Social team'),
      '#description' => $this->t("Tick the box to specify which data you want to share. Keep unchecked if you don’t want to share any data."),
      '#default_value' => $this->config('social_lets_connect_usage.settings')->get('usage_data_enabled'),
    ];
    $form['usage_data'] = [
      '#type' => 'checkboxes',
      '#options' => [
        'website_url' => $this->t('Community URL'),
        'entity_type_count' => $this->t('Number of items per entity (e.g., nr. of members, topics, events, etc.)'),
        'open_social_version' => $this->t('Open Social version'),
        'system_data' => $this->t('System data (operating system, PHP version, and extensions)'),
        'modules_installed' => $this->t('Add-on information (including installed modules, profiles, and themes)'),
      ],
      '#title' => $this->t('Select which usage data to share'),
      '#default_value' => $this->config('social_lets_connect_usage.settings')->get('usage_data'),
      '#states' => [
        'visible' => [
          ':input[name="usage_data_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Filter out unchecked items.
    $usage_data = $form_state->getValue('usage_data');
    foreach ($usage_data as $data => $value) {
      if (!$value) {
        unset($usage_data[$data]);
      }
    }

    // Save config.
    $config = $this->config('social_lets_connect_usage.settings');
    $config->set('usage_data', $usage_data);
    $usage_data_enabled = $form_state->getValue('usage_data_enabled');
    $config->set('usage_data_enabled', $usage_data_enabled);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}

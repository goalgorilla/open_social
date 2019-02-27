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
      '#markup' => $this->t('Open Social collects data to improve the product. We will never collect any personal identifiable information. You can choose what data you want to share with the Open Social team below. The data will be send nightly, in the background via a cron process, to the Open Social team.'),
    ];
    $form['usage_data'] = [
      '#type' => 'checkboxes',
      '#options' => [
        'website_url' => $this->t('Website url'),
        'entity_type_count' => $this->t('Entity type count (only which entities have how many items)'),
        'open_social_version' => $this->t('Open Social version'),
        'system_data' => $this->t('System data, including PHP version'),
        'modules_installed' => $this->t('Installed projects (which projects and versions are installed)'),
      ],
      '#title' => $this->t('Usage data'),
      '#description' => $this->t('Select which usage data should be shared. We will not collect user data or content. We only collect the data you choose to share.'),
      '#default_value' => $this->config('social_lets_connect_usage.settings')->get('usage_data'),
    ];
    $form['refresh_interval'] = [
      '#type' => 'select',
      '#options' => [
        86400 => $this->t('1 day'),
        604800 => $this->t('1 week'),
        2419200 => $this->t('Every 4 weeks'),
      ],
      '#title' => $this->t('Refresh interval'),
      '#description' => $this->t('Select how often the data should be send.'),
      '#default_value' => $this->config('social_lets_connect_usage.settings')->get('refresh_interval'),
      '#required' => TRUE,
    ];
    $form['url'] = [
      '#type' => 'url',
      '#title' => $this->t('URL'),
      '#description' => $this->t('Set the URL to which the data will be send.'),
      '#default_value' => $this->config('social_lets_connect_usage.settings')->get('url'),
      '#required' => FALSE,
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
    $config->set('usage_data', $usage_data)
      ->set('refresh_interval', (int) $form_state->getValue('refresh_interval'))
      ->set('url', $form_state->getValue('url'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}

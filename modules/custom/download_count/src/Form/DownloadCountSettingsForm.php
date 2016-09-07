<?php

namespace Drupal\download_count\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure download count settings.
 */
class DownloadCountSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'download_count_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['download_count.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('download_count.settings');
    $form['excluded file extensions'] = array(
      '#type' => 'details',
      '#title' => $this->t('Excluded file extensions'),
      '#open' => TRUE,
    );
    $form['excluded file extensions']['download_count_excluded_file_extensions'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Excluded file extensions'),
      '#default_value' => $config->get('download_count_excluded_file_extensions'),
      '#maxlength' => 255,
      '#description' => $this->t('To exclude files of certain types, enter the extensions to exclude separated by spaces. This is useful if you have private image fields and don\'t wish to include them in download counts.'),
    );

    $form['download_count_flood_control'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Flood Control Settings'),
      '#open' => FALSE,
    );
    $form['download_count_flood_control']['download_count_flood_limit'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Flood control limit'),
      '#size' => 10,
      '#default_value' => $config->get('download_count_flood_limit'),
      '#description' => $this->t('Maximum number of times to count the file download per time window. Enter 0 for no flood control limits.'),
    );
    $form['download_count_flood_control']['download_count_flood_window'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Flood control window'),
      '#size' => 10,
      '#default_value' => $config->get('download_count_flood_window'),
      '#description' => $this->t('Number of seconds in the time window for counting a file download.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('download_count.settings');
    $config->set('download_count_flood_window', $form_state->getValue('download_count_flood_window'))
      ->set('download_count_flood_limit', $form_state->getValue('download_count_flood_limit'))
      ->set('download_count_excluded_file_extensions', $form_state->getValue('download_count_excluded_file_extensions'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}

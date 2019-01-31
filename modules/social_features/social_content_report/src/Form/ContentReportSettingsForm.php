<?php

namespace Drupal\social_content_report\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EventSettingsForm.
 *
 * @package Drupal\social_content_report\Form
 */
class ContentReportSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'social_content_report.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_content_report_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_content_report.settings');

    // Make reason at reporting mandatory.
    $form['mandatory_reason'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Mandatory reason'),
      '#description' => $this->t('Whether users should fill in a mandatory reason or if it is optional.'),
      '#default_value' => $config->get('mandatory_reason'),
    );

    // Allow immediate unpublishing.
    $form['unpublish_immediately'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Unpublished immediately'),
      '#description' => $this->t('Whether the content is immediately unpublished if a user reports it as inappropriate.'),
      '#default_value' => $config->get('unpublish_immediately'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->configFactory->getEditable('social_content_report.settings')
      ->set('mandatory_reason', $form_state->get('mandatory_reason'))
      ->set('unpublish_immediately', $form_state->get('unpublish_immediately'))
      ->save();
  }

}

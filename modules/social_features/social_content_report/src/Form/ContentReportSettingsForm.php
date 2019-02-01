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
    $config = $this->configFactory->get('social_content_report.settings');

    // Allow immediate unpublishing.
    $form['unpublish_immediately'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unpublished immediately'),
      '#description' => $this->t('Whether the content is immediately unpublished if a user reports it as inappropriate.'),
      '#default_value' => $config->get('unpublish_immediately'),
    ];

    // A list of reason terms to display the reason textfield for.
    // @todo Add dependency injection.
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('report_reasons');
    foreach ($terms as $term) {
      $reason_terms[$term->tid] = $term->name;
    }

    $form['reasons_with_text'] = [
      '#type' => 'checkboxes',
      '#options' => $reason_terms,
      '#title' => $this->t('Terms with additional reason text'),
      '#description' => $this->t('Select the terms that will show an additional field where users can describe their.'),
      '#default_value' => $config->get('reasons_with_text'),
    ];

    // Make reason text at reporting mandatory.
    $form['mandatory_reason'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mandatory reason text'),
      '#description' => $this->t('Whether users should fill in a mandatory reason or if it is optional.'),
      '#default_value' => $config->get('mandatory_reason'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('social_content_report.settings')
      ->set('unpublish_immediately', $form_state->getValue('unpublish_immediately'))
      ->set('reasons_with_text', $form_state->getValue('reasons_with_text'))
      ->set('mandatory_reason', $form_state->getValue('mandatory_reason'))
      ->save();
  }

}

<?php

namespace Drupal\social_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AddressRegexPatternForm.
 */
class AddressRegexPatternForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'social_core.address.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'address_regex_pattern_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $address_config = $this->config('social_core.address.settings');

    $form['address_regex_pattern_fieldset'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    $form['address_regex_pattern_fieldset']['regex_pattern'] = [
      '#title' => $this->t('Regex pattern'),
      '#type' => 'textfield',
      '#description' => $this->t('The regex pattern will be used to clean empty variable on Address field, all pattern should be registered.'),
      '#required' => TRUE,
      '#default_value' => $address_config->get('regex_pattern'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $address_format = $form_state->getValue(['address_regex_pattern_fieldset', 'regex_pattern']);
    $address_config = $this->config('social_core.address.settings');

    $address_config->set('regex_pattern', $address_format)
      ->save();

    $this->messenger()->addStatus($this->t('The regex pattern has been updated.'));
  }

}

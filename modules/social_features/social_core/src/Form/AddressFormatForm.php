<?php

namespace Drupal\social_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AddressFormatForm.
 */
class AddressFormatForm extends ConfigFormBase {

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
    return 'address_format_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $address_config = $this->config('social_core.address.settings');

    $form['address_format_fieldset'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    $form['address_format_fieldset']['address_format'] = [
      '#title' => $this->t('Address format'),
      '#type' => 'textarea',
      '#description' => $this->t('You can set the format address to be rendered, check available tokens below.'),
      '#required' => TRUE,
      '#default_value' => $address_config->get('format'),
    ];

    $form['address_format_fieldset']['available_tokens'] = [
      '#title' => $this->t('Available tokens'),
      '#type' => 'details',
      '#open' => FALSE,
    ];

    $form['address_format_fieldset']['available_tokens']['token_table'] = [
      '#type' => 'table',
      '#header' => [
        'token' => $this->t('Token'),
        'description' => $this->t('Description'),
      ],
      '#rows' => [
        [
          'token' => '@address_line1%',
          'description' => t('Address line 1', [], ['context' => 'Address label']),
        ],
        [
          'token' => '@address_line2%',
          'description' => t('Address line 2', [], ['context' => 'Address label']),
        ],
        [
          'token' => '@address_line3%',
          'description' => t('Address line 3', [], ['context' => 'Address label']),
        ],
        [
          'token' => '@postal_code%',
          'description' => t('Postal code', [], ['context' => 'Address label']),
        ],
        [
          'token' => '@sorting_code%',
          'description' => t('Sorting code', [], ['context' => 'Address label']),
        ],
        [
          'token' => '@administrative_area%',
          'description' => t('Administrative area (e.g. State or Province)', [], ['context' => 'Address label']),
        ],
        [
          'token' => '@locality%',
          'description' => t('Locality (e.g. City)', [], ['context' => 'Address label']),
        ],
        [
          'token' => '@dependent_locality%',
          'description' => t('Dependent locality (e.g. Neighbourhood)', [], ['context' => 'Address label']),
        ],
        [
          'token' => '@country_code%',
          'description' => t('Country', [], ['context' => 'Address label']),
        ],
      ],
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
    $address_format = $form_state->getValue(['address_format_fieldset', 'address_format']);
    $address_config = $this->config('social_core.address.settings');

    $address_config->set('format', $address_format)
      ->save();

    $this->messenger()->addStatus($this->t('The address format has been updated.'));
  }

}

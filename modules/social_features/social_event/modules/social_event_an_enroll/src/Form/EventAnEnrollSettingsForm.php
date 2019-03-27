<?php

namespace Drupal\social_event_an_enroll\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EventAnEnrollSettingsForm.
 *
 * @package Drupal\social_event_an_enroll\Form
 */
class EventAnEnrollSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_an_enroll_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['social_event_an_enroll.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $event_an_enroll_config = $this->config('social_event_an_enroll.settings');

    $form['event_an_enroll'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable event enrollment for anonymous users'),
      '#description' => $this->t('Enabling this feature provides event organisers with the possibility to allow anonymous users to enroll in public events.'),
      '#default_value' => $event_an_enroll_config->get('event_an_enroll'),
    ];

    // Show the additional settings only when AN enroll is enabled.
    $form['event_an_enroll_default_value'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow anonymous users to enroll in new events by default'),
      '#description' => $this->t('When this setting is enabled, anonymous users are allowed to enroll in newly created events by default. The event organiser will still be able to change this if they have the permission.'),
      '#default_value' => $event_an_enroll_config->get('event_an_enroll_default_value'),
      '#states' => [
        'visible' => [
          'input[name="event_an_enroll"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['event_an_enroll_allow_edit'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow the author of an event to decide whether anonymous users can or cannot enroll in the event'),
      '#description' => $this->t('This will provide event organisers with the permission to decide whether anonymous users can enroll in their event.'),
      '#default_value' => $event_an_enroll_config->get('event_an_enroll_allow_edit'),
      '#states' => [
        'visible' => [
          'input[name="event_an_enroll_default_value"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['event_an_enroll_email_notify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify user by email after anonymous enrollment'),
      '#default_value' => $event_an_enroll_config->get('event_an_enroll_email_notify'),
      '#states' => [
        'visible' => [
          'input[name="event_an_enroll"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['event_an_enroll_email'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Email notification'),
      '#group' => 'email',
      '#states' => [
        // Hide the additional settings when notification is disabled.
        'visible' => [
          'input[name="event_an_enroll"]' => ['checked' => TRUE],
          'input[name="event_an_enroll_email_notify"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['event_an_enroll_email']['event_an_enroll_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $event_an_enroll_config->get('event_an_enroll_email_subject'),
      '#maxlength' => 180,
    ];

    $form['event_an_enroll_email']['event_an_enroll_email_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $event_an_enroll_config->get('event_an_enroll_email_body'),
      '#rows' => 15,
    ];
    // Add explanation about using tokens.
    $form['event_an_enroll_email']['event_an_enroll_email_token'] = [
      '#markup' => $this->t('To add the event name and link, use the following tokens: [node:title], [social_event_an_enroll:enrolled_event]'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('social_event_an_enroll.settings')
      ->set('event_an_enroll', $form_state->getValue('event_an_enroll'))
      ->set('event_an_enroll_default_value', $form_state->getValue('event_an_enroll_default_value'))
      ->set('event_an_enroll_allow_edit', $form_state->getValue('event_an_enroll_allow_edit'))
      ->set('event_an_enroll_email_notify', $form_state->getValue('event_an_enroll_email_notify'))
      ->set('event_an_enroll_email_subject', $form_state->getValue('event_an_enroll_email_subject'))
      ->set('event_an_enroll_email_body', $form_state->getValue('event_an_enroll_email_body'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}

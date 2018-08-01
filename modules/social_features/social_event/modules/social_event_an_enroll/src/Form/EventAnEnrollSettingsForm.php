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
      '#description' => $this->t('Enabling this feature will give public event organisers the possibility to allow anonymous users to enroll in these public events.'),
      '#default_value' => $event_an_enroll_config->get('event_an_enroll'),
    ];

    $form['event_an_enroll_email_notify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify user after anonymous enrollment'),
      '#default_value' => $event_an_enroll_config->get('event_an_enroll_email_notify'),
      '#states' => [
        // Hide the additional settings when notification is disabled.
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
    // Adds the token [event name].
    $form['event_an_enroll_email']['event_an_enroll_email_token'] = [
      '#markup' => $this->t('To add event name and link use tokens: [event name], [event url]'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('social_event_an_enroll.settings')
      ->set('event_an_enroll', $form_state->getValue('event_an_enroll'))
      ->set('event_an_enroll_email_notify', $form_state->getValue('event_an_enroll_email_notify'))
      ->set('event_an_enroll_email_subject', $form_state->getValue('event_an_enroll_email_subject'))
      ->set('event_an_enroll_email_body', $form_state->getValue('event_an_enroll_email_body'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}

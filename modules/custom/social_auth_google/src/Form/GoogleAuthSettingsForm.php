<?php

namespace Drupal\social_auth_google\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class GoogleAuthSettingsForm
 * @package Drupal\social_auth_google\Form
 */
class GoogleAuthSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_auth_google_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'social_auth_google.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_auth_google.settings');

    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Google+ App settings'),
      '#open' => TRUE,
      '#description' => $this->t('You need to first create a Google App at <a href="@url">@url</a>', [
        '@url' => 'https://console.developers.google.com',
      ]),
    ];

    $form['settings']['client_id'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Client ID'),
      '#default_value' => $config->get('client_id'),
      '#description' => $this->t('Copy the Client ID here'),
    ];

    $form['settings']['client_secret'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Client Secret'),
      '#default_value' => $config->get('client_secret'),
      '#description' => $this->t('Copy the Client Secret here'),
    );

    $form['settings']['status'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Active'),
      '#default_value' => $config->get('status'),
      '#description' => $this->t('Determines whether this social network can be used.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('social_auth_google.settings')
      ->set('client_id', $values['client_id'])
      ->set('client_secret', $values['client_secret'])
      ->set('status', $values['status'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
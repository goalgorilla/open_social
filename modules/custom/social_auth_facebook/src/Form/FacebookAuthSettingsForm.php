<?php

namespace Drupal\social_auth_facebook\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FacebookAuthSettingsForm
 * @package Drupal\social_auth_facebook\Form
 */
class FacebookAuthSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_auth_facebook_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'social_auth_facebook.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_auth_facebook.settings');

    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Facebook App settings'),
      '#open' => TRUE,
      '#description' => $this->t('You need to first create a Facebook App at <a href="@url">@url</a>', [
        '@url' => 'https://developers.facebook.com/apps',
      ]),
    ];

    $form['settings']['app_id'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Application ID'),
      '#default_value' => $config->get('app_id'),
      '#description' => $this->t('Copy the App ID of your Facebook App here. This value can be found from your App Dashboard.'),
    ];

    $form['settings']['app_secret'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Application Secret'),
      '#default_value' => $config->get('app_secret'),
      '#description' => $this->t('Copy the App Secret of your Facebook App here. This value can be found from your App Dashboard.'),
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
    $this->config('social_auth_facebook.settings')
      ->set('app_id', $values['app_id'])
      ->set('app_secret', $values['app_secret'])
      ->set('status', $values['status'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
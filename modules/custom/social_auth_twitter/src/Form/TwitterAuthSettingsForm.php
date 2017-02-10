<?php

namespace Drupal\social_auth_twitter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for Social Auth Twitter.
 */
class TwitterAuthSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array('social_auth_twitter.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_auth_twitter_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_auth_twitter.settings');

    $form['twitter_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Twitter OAuth settings'),
      '#open' => TRUE,
    );

    $form['twitter_settings']['consumer_key'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Consumer Key'),
      '#default_value' => $config->get('consumer_key'),
      '#description' => $this->t('Copy the Consumer Key here'),
    );

    $form['twitter_settings']['consumer_secret'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Consumer Secret'),
      '#default_value' => $config->get('consumer_secret'),
      '#description' => $this->t('Copy the Consumer Secret here'),
    );

    $form['twitter_settings']['status'] = array(
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

    $this->config('social_auth_twitter.settings')
      ->set('consumer_key', $values['consumer_key'])
      ->set('consumer_secret', $values['consumer_secret'])
      ->set('status', $values['status'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}

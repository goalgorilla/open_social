<?php

/**
 * @file
 * Contains \Drupal\social\SocialSettingsForm.
 */

namespace Drupal\social;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Social comments config form.
 */
class SocialSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_configure';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->configFactory->get('social.settings');

    $form = array();

    $form['social_comments'] = array(
      '#type' => 'fieldset',
      '#title' => t('Settings'),
    );

    $form['social_comments']['twitter_cache'] = array(
      '#type' => 'textfield',
      '#title' => t('Twitter cache expire'),
      '#description' => t('Enter in seconds'),
      '#default_value' => $config->get('twitter_cache'),
    );

    $form['social_comments']['google_cache'] = array(
      '#type' => 'textfield',
      '#title' => t('Google cache expire'),
      '#description' => t('Enter in seconds'),
      '#default_value' => $config->get('google_cache'),
    );

    $form['social_comments']['facebook_cache'] = array(
      '#type' => 'textfield',
      '#title' => t('Facebook cache expire'),
      '#description' => t('Enter in seconds'),
      '#default_value' => $config->get('facebook_cache'),
    );

    $form['social_comments']['google_api_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Google API key'),
      '#description' => t(
        'You can create it here !link',
        array(
          '!link' => l(
            t('Google API'),
            'https://code.google.com/apis/console',
            array(
              'attributes' => array(
                'target' => '_blank',
              ),
            )
          ),
        )
      ),
      '#default_value' => $config->get('google_api_key'),
    );

    $form['social_comments']['facebook_app_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Facebook App ID'),
      '#default_value' => $config->get('facebook_app_id'),
    );

    $form['social_comments']['facebook_app_secret'] = array(
      '#type' => 'textfield',
      '#title' => t('Facebook App Secret'),
      '#default_value' => $config->get('facebook_app_secret'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->configFactory->get('social.settings')
      ->set('twitter_cache', $form_state['values']['twitter_cache'])
      ->set('google_cache', $form_state['values']['google_cache'])
      ->set('facebook_cache', $form_state['values']['facebook_cache'])
      ->set('google_api_key', $form_state['values']['google_api_key'])
      ->set('facebook_app_id', $form_state['values']['facebook_app_id'])
      ->set('facebook_app_secret', $form_state['values']['facebook_app_secret'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}

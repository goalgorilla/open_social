<?php

/**
 * @file
 * Social administration settings.
 */

/**
 * Administrative settings form.
 */
function social_settings_form($form, &$form_state) {
  $form = array();

  $form['social_comments'] = array(
    '#type' => 'fieldset',
    '#title' => t('Settings'),
  );

  $form['social_comments']['twitter_cache'] = array(
    '#type' => 'textfield',
    '#title' => t('Twitter cache expire'),
    '#description' => t('Enter in seconds'),
    '#default_value' => variable_get('twitter_cache'),
  );

  $form['social_comments']['google_cache'] = array(
    '#type' => 'textfield',
    '#title' => t('Google cache expire'),
    '#description' => t('Enter in seconds'),
    '#default_value' => variable_get('google_cache'),
  );

  $form['social_comments']['facebook_cache'] = array(
    '#type' => 'textfield',
    '#title' => t('Facebook cache expire'),
    '#description' => t('Enter in seconds'),
    '#default_value' => variable_get('facebook_cache'),
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
    '#default_value' => variable_get('google_api_key'),
  );

  $form['social_comments']['facebook_app_id'] = array(
    '#type' => 'textfield',
    '#title' => t('Facebook App ID'),
    '#default_value' => variable_get('facebook_app_id'),
  );

  $form['social_comments']['facebook_app_secret'] = array(
    '#type' => 'textfield',
    '#title' => t('Facebook App Secret'),
    '#default_value' => variable_get('facebook_app_secret'),
  );

  return system_settings_form($form);
}

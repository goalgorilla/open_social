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

  $form['google'] = array(
    '#type' => 'fieldset',
    '#title' => t('Google settings'),
  );

  $form['google']['social_google_cache'] = array(
    '#type' => 'textfield',
    '#title' => t('Google cache expire'),
    '#description' => t('Enter in seconds'),
    '#default_value' => variable_get('social_google_cache', 600),
  );

  $form['google']['social_google_api_key'] = array(
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
    '#default_value' => variable_get('social_google_api_key'),
  );

  $form['facebook'] = array(
    '#type' => 'fieldset',
    '#title' => t('Facebook Settings'),
  );

  $form['facebook']['social_facebook_cache'] = array(
    '#type' => 'textfield',
    '#title' => t('Facebook cache expire'),
    '#description' => t('Enter in seconds'),
    '#default_value' => variable_get('social_facebook_cache', 600),
  );

  $form['facebook']['social_facebook_app_id'] = array(
    '#type' => 'textfield',
    '#title' => t('Facebook App ID'),
    '#default_value' => variable_get('social_facebook_app_id'),
  );

  $form['facebook']['social_facebook_app_secret'] = array(
    '#type' => 'textfield',
    '#title' => t('Facebook App Secret'),
    '#default_value' => variable_get('social_facebook_app_secret'),
  );

  $form['twitter'] = array(
    '#type' => 'fieldset',
    '#title' => t('Twitter Settings'),
  );

  $form['twitter']['social_twitter_cache'] = array(
    '#type' => 'textfield',
    '#title' => t('Twitter cache expire'),
    '#description' => t('Enter in seconds'),
    '#default_value' => variable_get('social_twitter_cache', 600),
  );

  return system_settings_form($form);
}

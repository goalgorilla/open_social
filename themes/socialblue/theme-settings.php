<?php

function socialblue_form_system_theme_settings_alter(&$form, \Drupal\Core\Form\FormStateInterface &$form_state, $form_id = NULL) {
  // Work-around for a core bug affecting admin themes. See issue #943212.
  if (isset($form_id)) {
    return;
  }

  $config = \Drupal::config('socialblue.settings');

  $form['open_social_settings'] = array(
    '#type' => 'vertical_tabs',
    '#title' => t('Opensocial settings'),
    '#weight' => -50,
    '#collapsible' => true,
    '#collapsed' => true,
  );

  $form['os_color_settings'] = array(
    '#type' => 'details',
    '#group' => 'open_social_settings',
    '#title' => t('Colors'),
    '#weight' => 10,
    '#collapsible' => true,
    '#collapsed' => true,
  );

  $form['os_font_settings'] = array(
    '#type' => 'details',
    '#group' => 'open_social_settings',
    '#title' => t('Fonts'),
    '#weight' => 20,
    '#collapsible' => true,
    '#collapsed' => true,
  );

  // Color tab.
  $form['os_color_settings']['color_primary'] = array(
    '#type' => 'textfield',
    '#title' => t('Primary color'),
    '#default_value' => $config->get('color_primary'),
  );
  $form['os_color_settings']['color_secondary'] = array(
    '#type' => 'textfield',
    '#title' => t('Secondary color'),
    '#default_value' => $config->get('color_secondary'),
  );
  $form['os_color_settings']['color_accents'] = array(
    '#type' => 'textfield',
    '#title' => t('Accents color'),
    '#default_value' => $config->get('color_accents'),
  );
  $form['os_color_settings']['border_radius'] = array(
    '#type' => 'textfield',
    '#title' => t('Border radius (px)'),
    '#default_value' => $config->get('border_radius'),
  );

  // Font tab.
  $fonts = array(); //
  $fonts []= 'Montserrat';
  $form['os_font_settings']['font_primary'] = array(
    '#type' => 'select',
    '#title' => t('Font'),
    '#options' => $fonts,
    '#default_value' => $config->get('font_primary'),
    '#description' => t("The font family to use."),
  );
}

<?php

function socialblue_form_system_theme_settings_alter(&$form, \Drupal\Core\Form\FormStateInterface &$form_state, $form_id = NULL) {
  // Work-around for a core bug affecting admin themes. See issue #943212.
  if (isset($form_id)) {
    return;
  }

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
    '#default_value' => theme_get_setting('colors.color_primary'),
  );
  $form['os_color_settings']['color_secondary'] = array(
    '#type' => 'textfield',
    '#title' => t('Secondary color'),
    '#default_value' => theme_get_setting('colors.color_secondary'),
  );
  $form['os_color_settings']['color_accents'] = array(
    '#type' => 'textfield',
    '#title' => t('Accents color'),
    '#default_value' => theme_get_setting('colors.color_accents'),
  );
  $form['os_color_settings']['logo'] = array(
    '#type' => 'textfield',
    '#title' => t('Logo'),
    '#default_value' => theme_get_setting('colors.logo'),
  );
  $form['os_color_settings']['favicon'] = array(
    '#type' => 'textfield',
    '#title' => t('Favicon'),
    '#default_value' => theme_get_setting('colors.favicon'),
  );
  $form['os_color_settings']['border_radius'] = array(
    '#type' => 'textfield',
    '#title' => t('Border radius'),
    '#default_value' => theme_get_setting('colors.border_radius'),
  );

  // Font tab.
  $form['os_font_settings']['font_parimary'] = array(
    '#type'          => 'textfield',
    '#title'         => t('Primary font'),
    '#default_value' => theme_get_setting('font.font_primary'),
    '#description'   => t("The primary font used in this theme."),
  );
  $form['os_font_settings']['font_fallback'] = array(
    '#type' => 'select',
    '#title' => t('Font fallback'),
    '#options' => array(
      'serif' => 'serif',
      'sans-serif' => 'sans-serif'
    ),
    '#default_value' => theme_get_setting('font.font_fallback'),
    '#description' => t("The fallback family."),
  );
}

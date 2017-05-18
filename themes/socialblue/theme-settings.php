<?php

function socialblue_form_system_theme_settings_alter(&$form, \Drupal\Core\Form\FormStateInterface &$form_state, $form_id = NULL) {
  // Work-around for a core bug affecting admin themes. See issue #943212.
  if (isset($form_id)) {
    return;
  }

  $system_theme_settings = \Drupal::configFactory()->get('system.theme')->get('default');

  if($system_theme_settings == 'socialblue' || $system_theme_settings == 'socialsaas') {
    $config = \Drupal::config($system_theme_settings . '.settings');
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
    '#title' => t('Border radius'),
    '#weight' => 20,
    '#collapsible' => true,
    '#collapsed' => true,
  );

  $form['os_font_settings'] = array(
    '#type' => 'details',
    '#group' => 'open_social_settings',
    '#title' => t('Fonts'),
    '#weight' => 10,
    '#collapsible' => true,
    '#collapsed' => true,
  );

  $form['os_color_settings']['border_radius'] = array(
    '#type' => 'textfield',
    '#title' => t('Border radius (px)'),
    '#default_value' => $config->get('border_radius'),
  );

  // Font tab.
  $fonts = [];
  if (\Drupal::service('module_handler')->moduleExists('social_font')) {
      /** @var \Drupal\social_font\Entity\Font $font_entities */
    foreach(\Drupal\social_font\Entity\Font::loadMultiple() as $font_entities) {
      $fonts [$font_entities->id()]= $font_entities->get('name')->value;
    }
  }

  $form['os_font_settings']['font_primary'] = array(
    '#type' => 'select',
    '#title' => t('Font'),
    '#options' => $fonts,
    '#default_value' => $config->get('font_primary'),
    '#description' => t("The font family to use."),
  );
}

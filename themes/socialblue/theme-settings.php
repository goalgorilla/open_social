<?php

/**
 * @file
 * Allows users to change the color scheme of themes.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\social_font\Entity\Font;

/**
 * Implements hook_form_FORM_ID_alter().
 */
function socialblue_form_system_theme_settings_alter(&$form, FormStateInterface &$form_state, $form_id = NULL) {
  // Work-around for a core bug affecting admin themes. See issue #943212.
  if (isset($form_id)) {
    return;
  }

  $theme = \Drupal::configFactory()->get('system.theme')->get('default');

  // Check for the current active theme.
  $active_theme = \Drupal::theme()->getActiveTheme()->getName();

  // Check for the current default theme.
  $system_theme_settings = \Drupal::configFactory()->get('system.theme')->get('default');

  // If the default theme is either socialblue or socialsaas then extend
  // the form in the appearance section.
  if (array_key_exists('socialbase', \Drupal::service('theme.manager')->getActiveTheme()->getBaseThemes())) {
    if ($active_theme == $system_theme_settings) {
      $config = \Drupal::config($theme . '.settings');

      $form['open_social_settings'] = [
        '#type' => 'vertical_tabs',
        '#title' => t('Opensocial settings'),
        '#weight' => -50,
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];

      $form['os_color_settings'] = [
        '#type' => 'details',
        '#group' => 'open_social_settings',
        '#title' => t('Border radius'),
        '#weight' => 20,
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];

      $form['os_font_settings'] = [
        '#type' => 'details',
        '#group' => 'open_social_settings',
        '#title' => t('Fonts'),
        '#weight' => 10,
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];

      $form['os_color_settings']['card_radius'] = [
        '#type' => 'number',
        '#title' => t('Card border radius (px)'),
        '#default_value' => $config->get('card_radius'),
        '#description' => t('Define the roundness of cards corners.'),
      ];

      $form['os_color_settings']['form_control_radius'] = [
        '#type' => 'number',
        '#title' => t('Form control border radius (px)'),
        '#default_value' => $config->get('form_control_radius'),
        '#description' => t('Define the roundness of the corners of form-controls, like <code>input</code>, <code>textarea</code> and <code>select</code>'),
      ];

      $form['os_color_settings']['button_radius'] = [
        '#type' => 'number',
        '#title' => t('Button border radius (px)'),
        '#default_value' => $config->get('button_radius'),
        '#description' => t('Define the roundness of buttons corners.'),
      ];

      $form['os_email_settings'] = [
        '#type' => 'details',
        '#group' => 'open_social_settings',
        '#title' => t('E-mail'),
        '#weight' => 30,
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];

      $form['os_email_settings']['email_logo'] = [
        '#type' => 'managed_file',
        '#title' => t('Logo for e-mails'),
        '#description' => t('Upload a logo which is shown in e-mail sent by the platform. This overrides the default logo that is also used in e-mails when no logo is provided here.'),
        '#default_value' => $config->get('email_logo'),
        '#upload_location' => 'public://',
        '#upload_validators' => [
          'file_validate_is_image' => [],
          'file_validate_extensions' => ['gif png jpg jpeg'],
        ],
      ];

      // Font tab.
      $fonts = [];
      if (\Drupal::service('module_handler')->moduleExists('social_font')) {

        /** @var \Drupal\social_font\Entity\Font $font_entities */
        foreach (Font::loadMultiple() as $font_entities) {
          $fonts[$font_entities->id()] = $font_entities->get('name')->value;
        }

      }

      $form['os_font_settings']['font_primary'] = [
        '#type' => 'select',
        '#title' => t('Font'),
        '#options' => $fonts,
        '#default_value' => $config->get('font_primary'),
        '#description' => t("The font family to use."),
      ];

    }

  }

}

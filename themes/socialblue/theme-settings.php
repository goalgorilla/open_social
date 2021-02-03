<?php

/**
 * @file
 * Allows users to change the color scheme of themes.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
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

      // Font Tab.
      $form['os_font_settings'] = [
        '#type' => 'details',
        '#group' => 'open_social_settings',
        '#title' => t('Fonts'),
        '#weight' => 10,
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];

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
        '#description' => t('The font family to use.'),
      ];

      // Box shadow tab.
      $form['os_box_shadow_settings'] = [
        '#type' => 'details',
        '#group' => 'open_social_settings',
        '#title' => t('Cards box shadow'),
        '#weight' => 20,
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];

      $form['os_box_shadow_settings']['override_box_shadow'] = [
        '#type' => 'checkbox',
        '#title' => t('Override default box shadow for cards'),
        '#default_value' => $config->get('override_box_shadow'),
        '#description' => t('Unselect this to disable box shadow.'),
      ];

      $form['os_box_shadow_settings']['details'] = [
        '#type' => 'details',
        '#title' => t('Card box shadow details'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#states' => [
          'invisible' => [
            ':input[name="override_box_shadow"]' => ['checked' => FALSE],
          ],
        ],
      ];

      $form['os_box_shadow_settings']['details']['box_shadow_color'] = [
        '#type' => 'color',
        '#title' => t('Box shadow color'),
        '#description' => t('Select box shadow color.'),
        '#default_value' => $config->get('box_shadow_color'),
      ];

      $form['os_box_shadow_settings']['details']['box_shadow_opacity'] = [
        '#type' => 'range',
        '#title' => t('Box shadow opacity'),
        '#default_value' => $config->get('box_shadow_opacity'),
        '#description' => t('Define the percentage of darkness of the hero gradient from 0 to 100.'),
        '#min' => 0,
        '#max' => 100,
      ];

      $form['os_box_shadow_settings']['details']['box_shadow_x_offset'] = [
        '#type' => 'number',
        '#title' => t('Box shadow X offset'),
        '#default_value' => $config->get('box_shadow_x_offset'),
        '#description' => t('Define the X offset for the box shadow.'),
      ];

      $form['os_box_shadow_settings']['details']['box_shadow_y_offset'] = [
        '#type' => 'number',
        '#title' => t('Box shadow Y offset'),
        '#default_value' => $config->get('box_shadow_y_offset'),
        '#description' => t('Define the Y offset for the box shadow.'),
      ];

      $form['os_box_shadow_settings']['details']['box_shadow_spread'] = [
        '#type' => 'number',
        '#title' => t('Box shadow spread value (px)'),
        '#default_value' => $config->get('box_shadow_spread'),
        '#description' => t('Define the spread value of the box shadow.'),
      ];

      $form['os_box_shadow_settings']['details']['box_shadow_blur'] = [
        '#type' => 'number',
        '#title' => t('Box shadow blur value'),
        '#default_value' => $config->get('box_shadow_blur'),
        '#description' => t('Define the blur value of the box shadow.'),
      ];

      // Border radius tab.
      $form['os_border_radius_settings'] = [
        '#type' => 'details',
        '#group' => 'open_social_settings',
        '#title' => t('Border radius'),
        '#weight' => 30,
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];

      $form['os_border_radius_settings']['card_radius'] = [
        '#type' => 'number',
        '#title' => t('Card border radius (px)'),
        '#default_value' => $config->get('card_radius'),
        '#description' => t('Define the roundness of cards corners.'),
      ];

      $form['os_border_radius_settings']['form_control_radius'] = [
        '#type' => 'number',
        '#title' => t('Form control border radius (px)'),
        '#default_value' => $config->get('form_control_radius'),
        '#description' => t('Define the roundness of the corners of form-controls, like <code>input</code>, <code>textarea</code> and <code>select</code>'),
      ];

      $form['os_border_radius_settings']['button_radius'] = [
        '#type' => 'number',
        '#title' => t('Button border radius (px)'),
        '#default_value' => $config->get('button_radius'),
        '#description' => t('Define the roundness of buttons corners.'),
      ];

      // Email Tab.
      $form['os_email_settings'] = [
        '#type' => 'details',
        '#group' => 'open_social_settings',
        '#title' => t('E-mail'),
        '#weight' => 40,
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

      // Hero tab.
      $form['os_hero_settings'] = [
        '#type' => 'details',
        '#group' => 'open_social_settings',
        '#title' => t('Hero'),
        '#weight' => 50,
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];

      $form['os_hero_settings']['hero_gradient_opacity'] = [
        '#type' => 'range',
        '#title' => t('Hero gradient'),
        '#default_value' => $config->get('hero_gradient_opacity'),
        '#description' => t('Define the percentage of darkness of the hero gradient from 0 to 100.'),
        '#min' => 0,
        '#max' => 100,
      ];

      // Styles tab.
      $form['os_style_settings'] = [
        '#type' => 'details',
        '#group' => 'open_social_settings',
        '#title' => t('Style'),
        '#weight' => 60,
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#access' => \Drupal::currentUser()->hasPermission('administer improved theme settings'),
      ];

      $form['os_style_settings']['style'] = [
        '#type' => 'select',
        '#title' => t('Style'),
        '#options' => [
          '' => t('Default'),
          'sky' => t('Sky (New)'),
        ],
        '#default_value' => $config->get('style'),
      ];

      // When GIN is our admin theme, update the GIN colors.
      if (\Drupal::configFactory()->get('system.theme')->get('admin') === 'gin') {
        $form['#submit'][] = 'socialblue_update_gin_color_settings';
      }

      // Ensure we save the file permanently.
      $form['#submit'][] = 'socialblue_save_email_logo';

    }

  }

}

/**
 * Updates the gin color settings from socialblue it's config.
 *
 * This ensures the brand color also applies to GIN.
 *
 * @param array $form
 *   The submitted form structure.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The state of the submitted form.
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function socialblue_update_gin_color_settings(array $form, FormStateInterface $form_state) {
  // Grab the default socialblue colors, these are set if the color settings
  // aren't overridden yet.
  $default_colors = \Drupal::configFactory()->getEditable('socialblue.settings')->getRawData();
  // Unfortunately the color module doesnt add the color details to the
  // $form_state. So we need to grab it from the config once overridden.
  // luckily color does set their submit function as first, so we can
  // safely assume the config uses the updated colors.
  $socialblue_colors = \Drupal::configFactory()->getEditable('color.theme.socialblue')->getRawData();

  // The brand colors are first of all coming from the overridden color
  // settings. But if that is not set, we will grab them from the
  // default Social Blue settings.
  $brand_primary = !empty($socialblue_colors) ? $socialblue_colors['palette']['brand-primary'] : $default_colors['color_primary'];
  $brand_secondary = !empty($socialblue_colors) ? $socialblue_colors['palette']['brand-secondary'] : $default_colors['color_secondary'];

  // See if we can update GIN settings with our brand colors.
  if (isset($brand_primary, $brand_secondary)) {
    $config = \Drupal::configFactory()->getEditable('gin.settings');
    if (!empty($config->getRawData())) {
      $gin_config = $config->getRawData();
      // Override preset colors as custom so we can fill in the hex colors.
      $gin_config['preset_accent_color'] = 'custom';
      $gin_config['preset_focus_color'] = 'custom';
      // Update the accent and focus with our branded colors.
      $gin_config['accent_color'] = $brand_primary;
      $gin_config['focus_color'] = $brand_secondary;
      $config->setData($gin_config);
      $config->save();
    }
  }
}

/**
 * Marks the e-mail logo file as permanent.
 *
 * This ensures the image is not cleaned up by Drupal's temporary file cleaning.
 *
 * @param array $form
 *   The submitted form structure.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The state of the submitted form.
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function socialblue_save_email_logo(array $form, FormStateInterface $form_state) {
  $email_logo = $form_state->getValue('email_logo');
  // If an e-mail logo was uploaded then we mark the uploaded file as permanent.
  if (!empty($email_logo)) {
    $file = File::load($email_logo[0]);
    $file->setPermanent();
    $file->save();
  }
}

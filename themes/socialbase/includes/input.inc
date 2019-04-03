<?php

/**
 * @file
 * The input inc file for the Social base theme.
 */

/**
 * Implements hook_preprocess_input().
 */
function socialbase_preprocess_input(&$variables) {

  if (isset($variables['element']['#type']) && $variables['element']['#type'] === 'radio') {
    // Only reaction on the visibility field.
    if (strpos($variables['element']['#id'], 'field-visibility') !== FALSE) {
      $title = $variables['element']['#title'];
      $variables['selected_material_icon'] = _socialbase_get_visibility_icon($title);
      if (isset($variables['element']['#return_value']) && isset($variables['element']['#default_value'])) {
        if ($variables['element']['#return_value'] === $variables['element']['#default_value']) {
          $variables['element']['active'] = 'active';
          $variables['active'] = 'active';
        }
      }
    }
  }

  // Set "add more managers" button style.
  if (isset($variables['element']['#array_parents']) && in_array('add_more', $variables['element']['#array_parents'], TRUE) && $variables['type'] == 'submit') {
    $variables['button_type'] = 'flat';
  }

  if (isset($variables['element']['#array_parents'])) {
    if (in_array('save_modal', $variables['element']['#array_parents'], TRUE)) {
      $variables['button_type'] = 'primary';
    }
  }

  // For all buttons in file upload set type to flat,
  // usually tables -> remove button.
  if (isset($variables['element']['#submit']) && in_array('file_managed_file_submit', $variables['element']['#submit'], TRUE)) {
    $variables['button_type'] = 'flat';
  }

  if (isset($variables['element']['#addsearchicon'])) {
    $variables['addsearchicon'] = TRUE;
  }

  // Button types are determined in the hook_form_alter and set to be
  // forced here again so the twig fill can use the variable params:
  // ['#button_type']
  // ['#button_level'].
  if (isset($variables['element']['#button_type'])) {

    if ($variables['element']['#button_type'] == 'primary') {
      $variables['button_type'] = 'primary';
    }

    if ($variables['element']['#button_type'] == 'danger' || $variables['element']['#button_type'] == 'default') {
      $variables['button_type'] = 'default';
    }

    if ($variables['element']['#button_type'] == 'flat') {
      $variables['button_type'] = 'flat';
    }

    if ($variables['element']['#button_type'] == 'accent') {
      $variables['button_type'] = 'accent';
    }

  }

  if (isset($variables['element']['#button_size'])) {
    if ($variables['element']['#button_size'] == 'small') {
      $variables['button_size'] = 'small';
    }
  }

  // If a split button doesn't have a button_type variable,
  // set it to default (fallback).
  if (isset($variables['element']['#split']) && !isset($variables['element']['#button_type'])) {
    $variables['button_type'] = 'default';
  }

  if (isset($variables['element']['#button_level'])) {
    if ($variables['element']['#button_level'] == 'raised') {
      $variables['button_level'] = 'raised';
    }
  }

  // Make sure we can use a span class caret in the button.
  if (!empty($variables['element']['#attributes']['data-caret']) && $variables['element']['#attributes']['data-caret'] === 'true') {
    $variables['element']['caret'] = [
      '#markup' => '<span class="caret"></span>',
    ];
  }

  // Render a simple VBO checkbox.
  if (isset($variables['element']['#id']) && $variables['element']['#id'] !== NULL && (strpos($variables['element']['#id'], 'edit-views-bulk-operations') !== FALSE || strpos($variables['element']['#id'], 'edit-social-views-bulk-operations') !== FALSE)
    && $variables['type'] == 'checkbox') {
    $variables['element']['#simple_checkbox'] = TRUE;
    $variables['simple_checkbox'] = TRUE;
  }
}

/**
 * Implements hook_preprocess_textarea().
 */
function socialbase_preprocess_textarea(&$variables) {

  if (isset($variables['element']['#array_parents'])) {
    $context = $variables['element']['#array_parents'];

    // Identify text areas that need to get autogrow behaviour.
    $autogrow_areas = [
      'field_post',
      'field_comment_body',
      'message',
    ];

    $c = array_intersect($context, $autogrow_areas);

    if (count($c) > 0) {
      $variables['autogrow'] = 'true';
    }

  }
}

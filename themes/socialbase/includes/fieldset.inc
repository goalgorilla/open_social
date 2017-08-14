<?php

/**
 * @file
 * The fieldset inc file for the Social base theme.
 */

/**
 * Implements hook_theme_suggestions_fieldset_alter().
 */
function socialbase_theme_suggestions_fieldset_alter(array &$suggestions, array $variables) {
  if (isset($variables['element']['#type']) && $variables['element']['#type'] == 'radios') {
    $suggestions[] = $variables['theme_hook_original'] . '__radios';
  }
}

/**
 * Implements hook_preprocess_fieldset().
 */
function socialbase_preprocess_fieldset(&$variables) {
  if (isset($variables['element']['#type']) && $variables['element']['#type'] == ('radios' || 'checkboxes')) {
    $variables['form_group'] = TRUE;
  }
}

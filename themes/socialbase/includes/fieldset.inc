<?php


/**
 * Implements hook_theme_suggestions_fieldset_alter().
 */
function socialbase_preprocess_fieldset(&$variables) {
  if (isset($variables['element']['#type']) && $variables['element']['#type'] == ('radios' || 'checkboxes')) {
      $variables['form_group'] = TRUE;
  }
}
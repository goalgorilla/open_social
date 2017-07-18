<?php

/**
 * @file
 * The dropdown inc file for the Social base theme.
 */

/**
 * Implements hook_preprocess_dropdown().
 */
function socialbase_preprocess_dropdown(&$variables) {
  if (isset($variables['active']) && is_numeric($variables['active'])) {
    $title = $variables['element'][$variables['active']]['#title'];
    $selected_icon = _socialbase_get_visibility_icon($title);
  }
  $variables['selected_icon'] = isset($selected_icon) ? $selected_icon : '';
}

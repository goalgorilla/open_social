<?php

/**
 * Implements hook_preprocess_dropdown().
 * @param $variables
 */
function socialbase_preprocess_dropdown(&$variables) {
  if (isset($variables['active']) && is_numeric($variables['active'])) {
    $title = $variables['element'][$variables['active']]['#title'];
    $selected_material_icon = _socialbase_get_visibility_icon($title);
  }
  $variables['selected_material_icon'] = isset($selected_material_icon) ? $selected_material_icon : '';
}

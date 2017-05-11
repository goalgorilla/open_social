<?php

/**
 * Implements hook_preprocess_html().
 */
function socialbase_preprocess_html(&$variables) {
  //indentify the difference between nodes and node/add & node/edit
  if ($variables['root_path'] == 'node') {
    $current_path = \Drupal::service('path.current')->getPath();
    $path_pieces = explode("/", $current_path);
    $path_target = array('add');
    if (count(array_intersect($path_pieces, $path_target)) > 0) {
      $variables['node_edit'] = TRUE;
    }
  }

  // Get all SVG Icons.
  $variables['svg_icons'] = file_get_contents(drupal_get_path('theme', 'socialbase') . '/assets/icons/icons.svg');
}

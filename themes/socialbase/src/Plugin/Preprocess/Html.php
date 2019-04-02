<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;

/**
 * Pre-processes variables for the "html" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("html")
 */
class Html extends PreprocessBase {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info) {
    parent::preprocess($variables, $hook, $info);

    // Identify the difference between nodes and node/add & node/edit.
    if ($variables['root_path'] == 'node') {
      $current_path = \Drupal::service('path.current')->getPath();
      $path_pieces = explode("/", $current_path);
      $path_target = ['add', 'edit'];
      if (count(array_intersect($path_pieces, $path_target)) > 0) {
        $variables['node_edit'] = TRUE;
      }
    }

    // Get all SVG Icons.
    $variables['svg_icons'] = file_get_contents(drupal_get_path('theme', 'socialbase') . '/assets/icons/icons.svg');

  }

}

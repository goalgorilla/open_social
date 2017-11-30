<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;

/**
 * Pre-processes variables for the "page_title" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("page_title")
 */
class PageTitle extends PreprocessBase {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info) {
    parent::preprocess($variables, $hook, $info);

    // Get the current path and if is it stream return a variable.
    $current_path = \Drupal::service('path.current')->getPath();

    if (strpos($current_path, 'stream') !== FALSE || strpos($current_path, 'explore') !== FALSE) {
      $variables['stream'] = TRUE;
    }

    // Check if it is a node.
    if (strpos($current_path, 'node') !== FALSE) {
      $variables['node'] = TRUE;
    }

    // Check if it is the edit/add/delete.
    $paths_to_exclude = [
      'edit',
      'add',
      'delete',
    ];

    $in_path = str_replace($paths_to_exclude, '', $current_path) !== $current_path;

    if ($in_path) {
      $variables['edit'] = TRUE;
    }

  }

}

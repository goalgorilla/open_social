<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\Core\Url;

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
    $current_url = Url::fromRoute('<current>');
    $current_path = $current_url->toString();

    if (strpos($current_path, 'profile') !== FALSE) {
      $profile_type = $variables['title']->getArguments();
      if (!empty($profile_type['@label'])) {
        $variables['title'] = t('Edit @label', ['@label' => $profile_type['@label']]);
      }
    }

    if (strpos($current_path, 'user') !== FALSE && strpos($current_path, 'edit') !== FALSE) {
      $variables['title'] = t('<em>Configure account settings:</em> @label', ['@label' => $variables['title']['#markup']]);
    }

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

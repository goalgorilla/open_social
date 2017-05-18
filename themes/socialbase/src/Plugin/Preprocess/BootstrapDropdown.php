<?php
/**
 * @file
 * Contains \Drupal\socialbase\Plugin\Preprocess\BootstrapDropdown.
 */

namespace Drupal\socialbase\Plugin\Preprocess;

/**
 * Pre-processes variables for the "bootstrap_dropdown" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("bootstrap_dropdown")
 */
class BootstrapDropdown extends \Drupal\bootstrap\Plugin\Preprocess\BootstrapDropdown {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info) {
    parent::preprocess($variables, $hook, $info);
    if (isset($variables['items']['#items']['publish']['element']['#button_type']) && $variables['items']['#items']['publish']['element']['#button_type'] == 'primary') {
      $variables['alignment'] = 'right';
    }

  }

}

<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;

/**
 * Pre-processes variables for the "dropdown" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("dropdown")
 */
class FieldDropdown extends PreprocessBase {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info) {
    parent::preprocess($variables, $hook, $info);

    if (isset($variables['active']) && is_numeric($variables['active'])) {
      $title = $variables['element'][$variables['active']]['#title'];
      $selected_icon = _socialbase_get_visibility_icon($title);
    }

    $variables['selected_icon'] = isset($selected_icon) ? $selected_icon : '';
  }

}

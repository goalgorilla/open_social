<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\Select as BaseSelect;

/**
 * Pre-processes variables for the "select" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("select")
 */
class Select extends BaseSelect {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info) {
    parent::preprocess($variables, $hook, $info);

    if (isset($variables['element']['#multiple']) && $variables['element']['#multiple'] == TRUE) {
      $variables['multiselect'] = TRUE;
    }

  }

}

<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Utility\Variables;

/**
 * Pre-processes variables for "bootstrap_dropdown__operations__actions" hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("bootstrap_dropdown__operations__actions")
 */
class DropdownActions extends Dropdown {

  /**
   * Function to preprocess the links.
   */
  protected function preprocessLinks(Variables $variables) {
    parent::preprocessLinks($variables);

    $variables['btn_context'] = 'actions';
    $variables->toggle['#split_button_attributes']['class'][] = 'pull-right';

    unset($variables->toggle['#attributes']['class']);
  }

}

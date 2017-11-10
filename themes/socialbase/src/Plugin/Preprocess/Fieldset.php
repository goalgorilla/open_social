<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\bootstrap\Utility\Element;
use Drupal\bootstrap\Utility\Variables;

/**
 * Pre-processes variables for the "fieldset" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("fieldset")
 */
class Fieldset extends PreprocessBase {

  /**
   * {@inheritdoc}
   */
  protected function preprocessElement(Element $element, Variables $variables) {
    if (isset($element['#type']) && $element['#type'] == ('radios' || 'checkboxes')) {
      $variables['form_group'] = TRUE;
    }

  }

}

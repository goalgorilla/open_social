<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\FormElement;
use Drupal\bootstrap\Utility\Element;
use Drupal\bootstrap\Utility\Variables;

/**
 * Pre-processes variables for the "form_element" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("form_element")
 */
class SocialBaseFormElement extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function preprocessElement(Element $element, Variables $variables) {

    // Tell the label it is in a switch form element.
    if (!empty($variables['element']['#attributes']['data-switch'])) {
      $variables['label']['#switch'] = TRUE;
    }

    // Use cards for the vertical tabs component.
    if ($variables['element']['#type'] === 'vertical_tabs') {
      $variables['attributes']['class'][] = 'card';
    }

    parent::preprocessElement($element, $variables);

  }

}

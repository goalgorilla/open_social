<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\FormElement as BaseFormElement;
use Drupal\bootstrap\Utility\Element;
use Drupal\bootstrap\Utility\Variables;

/**
 * Pre-processes variables for the "form_element" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("form_element")
 */
class FormElement extends BaseFormElement {

  /**
   * {@inheritdoc}
   */
  public function preprocessElement(Element $element, Variables $variables) {

    // Check if form element is part of
    // email_notifications and add class to label.
    if ($element->hasProperty('parents') && in_array('email_notifications', $element->getProperty('parents'))) {
      $variables['label']['#attributes']['class'][] = 'control-label--wide';
    }

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

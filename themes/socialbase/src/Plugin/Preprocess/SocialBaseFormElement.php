<?php

namespace Drupal\socialbase\Plugin\Preprocess;

/**
 * Pre-processes variables for the "form_element" theme hook.
 *
 * @ingroup plugins_preprocess
 * @deprecated
 * @see \Drupal\socialbase\Plugin\Preprocess\FormElement
 */
class SocialBaseFormElement extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function preprocessElement(Element $element, Variables $variables) {

    // Check if form element is part of
    // email_notifications and add class to label.
    if (in_array('email_notifications', $element['#parents'])) {
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

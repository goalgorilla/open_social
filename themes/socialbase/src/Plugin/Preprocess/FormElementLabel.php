<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\FormElementLabel as BaseFormElementLabel;
use Drupal\bootstrap\Utility\Element;
use Drupal\bootstrap\Utility\Variables;

/**
 * Pre-processes variables for the "form_element_label" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("form_element_label")
 */
class FormElementLabel extends BaseFormElementLabel {

  /**
   * {@inheritdoc}
   */
  public function preprocessElement(Element $element, Variables $variables) {

    if (isset($element['#id'])) {
      if (strpos($element['#id'], 'field-visibility') !== FALSE) {
        if (isset($element['#attributes']['title'])) {
          $description = $element['#attributes']['title'];
          $element['#attributes'] = [];
          $variables['description'] = $description;
        }
        // Set the materialize icon.
        $variables['icon'] = _socialbase_get_visibility_icon($element['#title']);
      }

      if ($element['#id'] == 'edit-message-0-value') {
        $variables['title_display'] = 'invisible';
      }

      // Date fields that need labels to distinguish date from time fields
      // These include daterange fields already as well.
      // @TODO update date fields of event to daterange fields and remove
      // the last 4 variables of this array.
      $date_fields = [
        'edit-field-date-0-value-time',
        'edit-field-date-0-value-date',
        'edit-field-date-0-end-value-date',
        'edit-field-date-0-end-value-time',
        'edit-field-event-date-0-value-date',
        'edit-field-event-date-0-value-time',
        'edit-field-event-date-end-0-value-date',
        'edit-field-event-date-end-0-value-time',

      ];

      if (in_array($element['#id'], $date_fields)) {
        $variables['title_display'] = 'above';
      }

      // Add class to labels for locale settings on user form
      // To make select elements consistent in placement and width.
      $locale_settings = [
        'edit-timezone--2',
        'edit-preferred-langcode',
      ];

      if (in_array($element['#id'], $locale_settings)) {
        $variables->addClass('control-label--wide');
      }
    }

    parent::preprocessElement($element, $variables);

  }

}

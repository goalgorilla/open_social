<?php

namespace Drupal\social_user\Plugin\better_exposed_filters\filter;

use Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\FilterWidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * HTML5 date widget implementation.
 *
 * @BetterExposedFiltersFilterWidget(
 *   id = "bef_html5_date",
 *   label = @Translation("HTML5 Date"),
 * )
 */
class Html5Date extends FilterWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable($filter = NULL, array $filter_options = []): bool {
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    $is_applicable = FALSE;

    if ((is_a($filter, 'Drupal\views\Plugin\views\filter\Date') || !empty($filter->date_handler)) && !$filter->isAGroup()) {
      $is_applicable = TRUE;
    }

    return $is_applicable;
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state): void {
    $field_id = $this->getExposedFilterFieldId();

    // Handle wrapper element added to exposed filters
    // in https://www.drupal.org/project/drupal/issues/2625136.
    $wrapper_id = $field_id . '_wrapper';

    if (!isset($form[$field_id]) && isset($form[$wrapper_id])) {
      $form[$wrapper_id][$field_id . '_op']['#title'] = t('Time frame');
      $form[$wrapper_id][$field_id . '_op']['#title_display'] = 'before';
      $form[$wrapper_id][$field_id . '_op']['#options']['between'] = t('Between');
      $form[$wrapper_id][$field_id . '_op']['#options']['>'] = t('After');
      $form[$wrapper_id][$field_id . '_op']['#options']['<'] = t('Before');
      $element = &$form[$wrapper_id][$field_id];
    }
    else {
      $element = &$form[$field_id];
    }

    /*
     * Standard Drupal date field. Depending on the settings, the field
     * can be at $element (single field) or
     * $element[subfield] for two-value date fields or filters
     * with exposed operators.
     */
    $fields = ['min', 'max', 'value'];

    if (count(array_intersect($fields, array_keys($element)))) {
      foreach ($fields as $field) {
        if (isset($element[$field])) {
          $element[$field]['#type'] = 'date';
          $element[$field]['#title'] = t('Date');
          $element[$field]['#attributes']['type'] = 'date';
          $element[$field]['#attributes']['class'][] = 'bef-html5-date';
        }
      }
    }
    else {
      $element['#type'] = 'date';
      $element['#title'] = t('Date');
      $element['#attributes']['type'] = 'date';
      $element['#attributes']['class'][] = 'bef-html5-date';
    }
  }

}

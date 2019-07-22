<?php

namespace Drupal\social_search\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Plugin\views\filter\SearchApiDate as DateTimeDate;

/**
 * Defines a filter for filtering on dates.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("social_date_filter")
 */
class SocialDate extends DateTimeDate {

  /**
   * {@inheritdoc}
   */
  public function operators() {
    $operators = parent::operators();

    $operators['<']['title'] = $this->t('Before');
    $operators['>']['title'] = $this->t('After');
    $operators['between']['title'] = $this->t('Between');

    $operators_to_keep = [
      '<',
      '>',
      'between',
    ];

    // Remove unnecessary exposed operators.
    foreach ($operators as $operator_name => $value) {
      if (is_string($operator_name) && !in_array($operator_name, $operators_to_keep, FALSE)) {
        if (!empty($operators[$operator_name])) {
          unset($operators[$operator_name]);
        }
      }
    }

    return $operators;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    // Update form values for the options.
    if (!empty($form['field_event_date_op'])) {
      $form['settings'] = [
        '#type' => 'details',
        '#title' => $this->t('Date of Event'),
        '#attributes' => [
          'class' => [
            'filter',
          ],
        ],
      ];

      // Unset field title, the settings one already has it.
      $form['field_event_date_op']['#title'] = '';

      // No more textfields!
      if (!empty($form['value'])) {
        if (!empty($form['value']['value'])) {
          $form['value']['value']['#type'] = 'date';
        }
        if (!empty($form['value']['min'])) {
          $form['value']['min']['#type'] = 'date';
          $form['value']['min']['#title'] = '';
        }
        if (!empty($form['value']['max'])) {
          $form['value']['max']['#type'] = 'date';
        }
      }
    }
  }
  
}

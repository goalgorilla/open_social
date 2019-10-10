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
  public function acceptExposedInput($input) {
    if (empty($this->options['exposed'])) {
      return TRUE;
    }

    // Don't filter if value(s) are empty.
    if (!empty($this->options['expose']['use_operator']) && !empty($this->options['expose']['operator_id'])) {
      $operator = $input[$this->options['expose']['operator_id']];
    }
    else {
      $operator = $this->operator;
    }

    // Custom override to ensure that when users
    // filter on something different than the event type
    // we also don't use an event field filter.
    if (!empty($input['type']) && $input['type'] !== 'event') {
      $input['field_event_date']['value'] = '';
      $input['field_event_date']['max'] = '';
      $input['field_event_date']['min'] = '';
    }

    // Fallback for exposed operator.
    if ($operator === NULL && $this->realField === 'created') {
      // Check if we have it in the query.
      $operatorfromurl = \Drupal::request()->query->get('created_op');
      if (!empty($operatorfromurl)) {
        $this->operator = $operatorfromurl;
        $input['created_op'] = $operatorfromurl;
        $this->view->exposed_raw_input = $this->view->getExposedInput();
      }
    }

    $return = parent::acceptExposedInput($input);

    if (!$return) {
      // Override for the "(not) empty" operators.
      $operators = $this->operators();
      if ($operators[$this->operator]['values'] === 0) {
        return TRUE;
      }
    }

    return $return;
  }

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

    // Key is form field name, value is title name.
    $form_keys = [
      'field_event_date_op' => $this->t('Date of Event'),
      'created_op' => $this->t('Registration Date'),
    ];

    // Update form values for the options.
    foreach ($form_keys as $key => $title) {
      if (!empty($form[$key])) {
        $form['settings'] = [
          '#type' => 'details',
          '#title' => $title,
          '#attributes' => [
            'class' => [
              'filter',
            ],
          ],
        ];

        // Unset field title, the settings one already has it.
        $form[$key]['#title'] = '';

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

}

<?php

namespace Drupal\search_api\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Plugin\views\SearchApiHandlerTrait;

/**
 * Provides a trait to use for Search API Views filters.
 */
trait SearchApiFilterTrait {

  use SearchApiHandlerTrait;

  /**
   * Adds a form for entering the value or values for the filter.
   *
   * Overridden to remove fields that won't be used (but aren't hidden either
   * because of a small bug/glitch in the original form code – see #2637674).
   *
   * @see \Drupal\views\Plugin\views\filter\FilterPluginBase::valueForm()
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

    if (isset($form['value']['min'])) {
      if (!$this->operatorValues(2)) {
        unset($form['value']['min'], $form['value']['max']);
      }
    }
  }

  /**
   * Adds a filter to the search query.
   *
   * Overridden to avoid errors because of SQL-specific functionality being used
   * when "Many To One" is used as a base class.
   *
   * @see \Drupal\views\Plugin\views\filter\ManyToOne::opHelper()
   */
  protected function opHelper() {
    if (empty($this->value)) {
      return;
    }
    // @todo Use "IN"/"NOT IN" instead, once available.
    $conjunction = $this->operator == 'or' ? 'OR' : 'AND';
    $operator = $this->operator == 'not' ? '<>' : '=';
    $filter = $this->getQuery()->createConditionGroup($conjunction);
    foreach ($this->value as $value) {
      $filter->addCondition($this->realField, $value, $operator);
    }
    $this->getQuery()->addConditionGroup($filter, $this->options['group']);
  }

}

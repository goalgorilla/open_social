<?php

namespace Drupal\search_api\Plugin\views\argument;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\UncacheableDependencyTrait;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;

/**
 * Defines a contextual filter for applying Search API conditions.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("search_api_argument")
 */
class SearchApiStandard extends ArgumentPluginBase {

  use UncacheableDependencyTrait;

  /**
   * The Views query object used by this contextual filter.
   *
   * @var \Drupal\search_api\Plugin\views\query\SearchApiQuery
   */
  public $query;

  /**
   * The operator to use for multiple arguments.
   *
   * Either "and" or "or".
   *
   * @var string
   *
   * @see \Drupal\views\Plugin\views\argument\ArgumentPluginBase::unpackArgumentValue()
   */
  public $operator;

  /**
   * {@inheritdoc}
   */
  public function defaultActions($which = NULL) {
    $defaults = parent::defaultActions();
    unset($defaults['summary']);

    if ($which) {
      return isset($defaults[$which]) ? $defaults[$which] : NULL;
    }
    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['break_phrase'] = array('default' => FALSE);
    $options['not'] = array('default' => FALSE);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Allow passing multiple values.
    $form['break_phrase'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Allow multiple values'),
      '#description' => $this->t('If selected, users can enter multiple values in the form of 1+2+3 (for OR) or 1,2,3 (for AND).'),
      '#default_value' => !empty($this->options['break_phrase']),
      '#fieldset' => 'more',
    );

    $form['not'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude'),
      '#description' => $this->t('If selected, the values entered for the filter will be excluded rather than limiting the view to those values.'),
      '#default_value' => !empty($this->options['not']),
      '#fieldset' => 'more',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $this->fillValue();

    $condition_operator = empty($this->options['not']) ? '=' : '<>';

    if (count($this->value) > 1) {
      $conditions = $this->query->createConditionGroup(Unicode::strtoupper($this->operator));
      // $conditions will be NULL if there were errors in the query.
      if ($conditions) {
        foreach ($this->value as $value) {
          $conditions->addCondition($this->realField, $value, $condition_operator);
        }
        $this->query->addConditionGroup($conditions);
      }
    }
    else {
      $this->query->addCondition($this->realField, reset($this->value), $condition_operator);
    }
  }

  /**
   * Fills $this->value and $this->operator with data from the argument.
   *
   * Uses
   * \Drupal\views\Plugin\views\argument\ArgumentPluginBase::unpackArgumentValue()
   * if appropriate.
   */
  protected function fillValue() {
    if (isset($this->value)) {
      return;
    }
    if (!empty($this->options['break_phrase'])) {
      $this->unpackArgumentValue(TRUE);
    }
    else {
      $this->value = array($this->argument);
      $this->operator = 'and';
    }
  }

}

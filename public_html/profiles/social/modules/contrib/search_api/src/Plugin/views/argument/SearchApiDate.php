<?php

namespace Drupal\search_api\Plugin\views\argument;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\search_api\UncacheableDependencyTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a contextual filter for conditions on date fields.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("search_api_date")
 */
class SearchApiDate extends SearchApiStandard {

  use UncacheableDependencyTrait;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface|null
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $plugin */
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $plugin->setDateFormatter($container->get('date.formatter'));

    return $plugin;
  }

  /**
   * Retrieves the date formatter.
   *
   * @return \Drupal\Core\Datetime\DateFormatterInterface
   *   The date formatter.
   */
  public function getDateFormatter() {
    return $this->dateFormatter ?: \Drupal::service('date.formatter');
  }

  /**
   * Sets the date formatter.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The new date formatter.
   *
   * @return $this
   */
  public function setDateFormatter(DateFormatterInterface $date_formatter) {
    $this->dateFormatter = $date_formatter;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $this->fillValue();
    if ($this->value === FALSE) {
      $this->abort();
      return;
    }

    $outer_conjunction = strtoupper($this->operator);

    if (empty($this->options['not'])) {
      $condition_operator = '=';
      $inner_conjunction = 'OR';
    }
    else {
      $condition_operator = '<>';
      $inner_conjunction = 'AND';
    }

    if (!empty($this->value)) {
      $outer_conditions = $this->query->createConditionGroup($outer_conjunction);
      // @todo Refactor to use only a single nested filter, and only if
      //   necessary. $value_conditions will currently only ever contain a
      //   single child – a condition or a nested filter with two conditions.
      foreach ($this->value as $value) {
        $value_conditions = $this->query->createConditionGroup($inner_conjunction);
        $values = explode(';', $value);
        $values = array_map(array($this, 'getTimestamp'), $values);
        if (in_array(FALSE, $values, TRUE)) {
          $this->abort();
          return;
        }
        $is_range = (count($values) > 1);

        $inner_conditions = ($is_range ? $this->query->createConditionGroup('AND') : $value_conditions);
        $range_op = (empty($this->options['not']) ? '>=' : '<');
        $inner_conditions->addCondition($this->realField, $values[0], $is_range ? $range_op : $condition_operator);
        if ($is_range) {
          $range_op = (empty($this->options['not']) ? '<=' : '>');
          $inner_conditions->addCondition($this->realField, $values[1], $range_op);
          $value_conditions->addConditionGroup($inner_conditions);
        }
        $outer_conditions->addConditionGroup($value_conditions);
      }

      $this->query->addConditionGroup($outer_conditions);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function title() {
    if (!empty($this->argument)) {
      $this->fillValue();
      $dates = array();
      foreach ($this->value as $date) {
        $date_parts = explode(';', $date);

        $ts = $this->getTimestamp($date_parts[0]);
        $date_string = $this->getDateFormatter()->format($ts, 'short');
        if (count($date_parts) > 1) {
          $ts = $this->getTimestamp($date_parts[1]);
          $date_string .= ' – ' . $this->getDateFormatter()->format($ts, 'short');
        }

        if ($date_string) {
          $dates[] = $date_string;
        }
      }
      return $dates ? implode(', ', $dates) : Html::escape($this->argument);
    }

    return Html::escape($this->argument);
  }

  /**
   * Converts a value to a timestamp, if it isn't one already.
   *
   * @param string|int $value
   *   The value to convert. Either a timestamp, or a date/time string as
   *   recognized by strtotime().
   *
   * @return int|false
   *   The parsed timestamp, or FALSE if an illegal string was passed.
   */
  protected function getTimestamp($value) {
    if (is_numeric($value)) {
      return (int) $value;
    }

    return strtotime($value);
  }

  /**
   * {@inheritdoc}
   */
  protected function unpackArgumentValue() {
    // Set up the defaults.
    if (!isset($this->value)) {
      $this->value = array();
    }
    if (!isset($this->operator)) {
      $this->operator = 'or';
    }

    if (empty($this->argument)) {
      return;
    }

    if (preg_match('/^([-\d;:\s]+\+)*[-\d;:\s]+$/', $this->argument)) {
      // The '+' character in a query string may be parsed as ' '.
      $this->value = explode('+', $this->argument);
    }
    elseif (preg_match('/^([-\d;:\s]+,)*[-\d;:\s]+$/', $this->argument)) {
      $this->operator = 'and';
      $this->value = explode(',', $this->argument);
    }

    // Keep an "error" value if invalid strings were given.
    if (!empty($this->argument) && (empty($this->value) || !is_array($this->value))) {
      $this->value = FALSE;
    }
  }

  /**
   * Aborts the associated query due to an illegal argument.
   *
   * @see \Drupal\search_api\Plugin\views\query\SearchApiQuery::abort()
   */
  protected function abort() {
    $variables['@field'] = $this->definition['group'] . ': ' . $this->definition['title'];
    $this->query->abort(new FormattableMarkup('Illegal argument passed to @field contextual filter.', $variables));
  }

}

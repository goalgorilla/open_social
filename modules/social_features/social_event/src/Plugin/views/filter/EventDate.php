<?php

namespace Drupal\social_event\Plugin\views\filter;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Filter events by start date and end date.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("social_event_date_filter")
 */
class EventDate extends InOperator {

  /**
   * {@inheritdoc}
   */
  protected $valueFormType = 'radios';

  /**
   * Flag to indicate Upcoming events.
   */
  const UPCOMING_EVENTS = 1;

  /**
   * Flag to indicate Past events.
   */
  const PAST_EVENTS = 2;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->definition['options callback'] = [$this, 'generateOptions'];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $value = (int) current($this->value);

    if (empty($value)) {
      return;
    }
    $this->ensureMyTable();
    $now = $this->query->getDateFormat('NOW()', DateTimeItemInterface::DATETIME_STORAGE_FORMAT, TRUE);
    // Get the time at past midnight and next midnight time.
    $past_midnight = $this->query->getDateFormat('NOW()', 'Y-m-d\T00:00:00', TRUE);
    $next_midnight = $this->query->getDateFormat('NOW()', 'Y-m-d\T23:59:59', TRUE);

    $configuration = [
      'table' => 'node__field_event_date_end',
      'field' => 'entity_id',
      'left_table' => '',
      'left_field' => 'nid',
    ];

    $configuration_all_day = [
      'table' => 'node__field_event_all_day',
      'field' => 'entity_id',
      'left_table' => '',
      'left_field' => 'nid',
    ];

    $join = Views::pluginManager('join')
      ->createInstance('standard', $configuration);
    $alias = $this->query->addRelationship($configuration['table'], $join, 'node_field_data');
    $field_end = $this->query->getDateFormat($alias . '.field_event_date_end_value', DateTimeItemInterface::DATETIME_STORAGE_FORMAT, TRUE);

    $all_day_join = Views::pluginManager('join')
      ->createInstance('standard', $configuration_all_day);
    $all_day_alias = $this->query->addRelationship($configuration_all_day['table'], $all_day_join, 'node_field_data');
    $all_day = $all_day_alias . '.field_event_all_day_value';

    $field = "{$this->tableAlias}.{$this->realField}";
    $field = $this->query->getDateFormat($field, DateTimeItemInterface::DATETIME_STORAGE_FORMAT, TRUE);

    switch ($value) {
      case self::UPCOMING_EVENTS:
        $this->query->addWhereExpression($this->options['group'], "({$field} >= {$now}
        OR
        ({$now} >= {$field} AND ({$now} < {$field_end} OR ({$all_day} > 0 AND {$past_midnight} <= {$field_end} AND {$field_end} < {$next_midnight}))))");
        break;

      case self::PAST_EVENTS:
        $this->query->addWhereExpression($this->options['group'], "
        (({$now} >= {$field_end}) AND ({$past_midnight} > {$field_end}))
        OR
        ({$field_end} IS NULL AND {$now} >= {$field})");
        break;
    }
  }

  /**
   * Retrieves the allowed values for the date filter.
   *
   * @return array
   *   An array of allowed values in the form key => label.
   */
  public function generateOptions() {
    return [
      self::UPCOMING_EVENTS => $this->t('Ongoing and upcoming events'),
      self::PAST_EVENTS => $this->t('Past events'),
    ];
  }

}

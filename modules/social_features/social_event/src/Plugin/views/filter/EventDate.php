<?php

namespace Drupal\social_event\Plugin\views\filter;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\Plugin\views\join\JoinPluginBase;
use Drupal\views\Plugin\views\query\Sql;
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
   * The value form type.
   *
   * @var string
   */
  protected $valueFormType = 'radios';

  /**
   * Flag to indicate Upcoming events.
   */
  public const UPCOMING_EVENTS = 1;

  /**
   * Flag to indicate Past events.
   */
  public const PAST_EVENTS = 2;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL): void {
    parent::init($view, $display, $options);
    $this->definition['options callback'] = [$this, 'generateOptions'];
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    /** @var Sql $query */
    $query = $this->query;

    $value = (int) current($this->value);

    if (empty($value)) {
      return;
    }
    $this->ensureMyTable();
    // Get base table, this filter is in several views,
    // and base table can be different, e.g. - for group events (group_content).
    $base_table = $this->relationship ?: $this->view->storage->get('base_table');
    $now = $this->query->getDateFormat('NOW()', DateTimeItemInterface::DATETIME_STORAGE_FORMAT, TRUE);
    // Get the time at past midnight and next midnight time.
    $past_midnight = $this->query->getDateFormat('NOW()', 'Y-m-d\T00:00:00', TRUE);
    $next_midnight = $this->query->getDateFormat('NOW()', 'Y-m-d\T23:59:59', TRUE);

    $configuration = [
      'table' => 'node__field_event_date_end',
      'field' => 'entity_id',
      'left_table' => $base_table,
      'left_field' => 'nid',
    ];

    $configuration_all_day = [
      'table' => 'node__field_event_all_day',
      'field' => 'entity_id',
      'left_table' => $base_table,
      'left_field' => 'nid',
    ];

    /** @var JoinPluginBase $join */
    $join = Views::pluginManager('join')
      ->createInstance('standard', $configuration);
    $alias = $query->addRelationship($configuration['table'], $join, $base_table);
    $field_end = $query->getDateFormat($alias . '.field_event_date_end_value', DateTimeItemInterface::DATETIME_STORAGE_FORMAT, TRUE);

    /** @var JoinPluginBase $all_day_join */
    $all_day_join = Views::pluginManager('join')
      ->createInstance('standard', $configuration_all_day);
    $all_day_alias = $query->addRelationship($configuration_all_day['table'], $all_day_join, $base_table);
    $all_day = $all_day_alias . '.field_event_all_day_value';

    $field = "{$this->tableAlias}.{$this->realField}";
    $field = $query->getDateFormat($field, DateTimeItemInterface::DATETIME_STORAGE_FORMAT, TRUE);

    switch ($value) {
      case self::UPCOMING_EVENTS:
        $query->addWhereExpression($this->options['group'], "({$field} >= {$now}
        OR
        ({$now} >= {$field} AND ({$now} < {$field_end} OR ({$all_day} > 0 AND {$past_midnight} <= {$field_end} AND {$field_end} < {$next_midnight}))))");
        break;

      case self::PAST_EVENTS:
        $query->addWhereExpression($this->options['group'], "
        (({$now} >= {$field_end}) OR ({$all_day} > 0 AND {$past_midnight} > {$field_end}))
        OR
        ({$field_end} IS NULL AND {$now} >= {$field})");
        break;
    }

    $this->query = $query;
  }

  /**
   * Retrieves the allowed values for the date filter.
   *
   * @return array
   *   An array of allowed values in the form key => label.
   */
  public function generateOptions(): array {
    return [
      self::UPCOMING_EVENTS => $this->t('Ongoing and upcoming events'),
      self::PAST_EVENTS => $this->t('Past events'),
    ];
  }

}

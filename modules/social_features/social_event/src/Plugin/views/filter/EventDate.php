<?php

namespace Drupal\social_event\Plugin\views\filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter events by start date and end date.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("social_event_event_date_filter")
 */
class EventDate extends InOperator {

  protected $valueFormType = 'radios';

  const UPCOMING_EVENTS = 1;
  const PAST_EVENTS = 2;

  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->definition['options callback'] = array($this, 'generateOptions');
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
    $now = $this->query->getDateFormat('NOW()', DATETIME_DATETIME_STORAGE_FORMAT, TRUE);
    $configuration = [
      'table' => 'node__field_event_date_end',
      'field' => 'entity_id',
      'left_table' => '',
      'left_field' => 'nid',
    ];

    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    $alias = $this->query->addRelationship($configuration['table'], $join, 'node_field_data');
    $field_end = $this->query->getDateFormat($alias . '.field_event_date_end_value', DATETIME_DATETIME_STORAGE_FORMAT, TRUE);
    $field = "{$this->tableAlias}.{$this->realField}";
    $field = $this->query->getDateFormat($field, DATETIME_DATETIME_STORAGE_FORMAT, TRUE);

    switch ($value) {
      case self::UPCOMING_EVENTS:
        $this->query->addWhereExpression($this->options['group'], "({$field} >= {$now}) OR ({$field} <= {$now} AND {$field_end} > {$now})");
        break;

      case self::PAST_EVENTS:
        $this->query->addWhereExpression($this->options['group'], "
        ({$now} >= {$field_end})
        OR
        ({$field_end} IS NULL AND {$now} >= {$field})");
        break;
    }
  }

  public function generateOptions() {
    return [
      self::UPCOMING_EVENTS => $this->t('Upcoming events'),
      self::PAST_EVENTS => $this->t('Past events'),
    ];
  }

}

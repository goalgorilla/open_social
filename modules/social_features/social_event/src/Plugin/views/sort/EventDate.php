<?php

namespace Drupal\social_event\Plugin\views\sort;

use Drupal\views\Plugin\views\query\Sql;
use Drupal\views\Plugin\views\sort\Date;
use Drupal\social_event\Plugin\views\filter\EventDate as EventDateFilter;

/**
 * Basic sort handler for passed events.
 *
 * @ViewsSort("social_event_date_sort")
 */
class EventDate extends Date {

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    $this->ensureMyTable();
    $order = ($this->view->exposed_data[$this->realField] === EventDateFilter::UPCOMING_EVENTS) ? 'ASC' : 'DESC';

    /** @var Sql $query */
    $query = $this->query;
    $query->addOrderBy($this->tableAlias, $this->realField, $order);
    $this->query = $query;
  }

}

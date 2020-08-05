<?php

namespace Drupal\social_event\Plugin\views\sort;

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
  public function query() {
    $this->ensureMyTable();
    $order = ($this->view->exposed_data[$this->realField] == EventDateFilter::UPCOMING_EVENTS) ? 'ASC' : 'DESC';
    $this->query->addOrderBy($this->tableAlias, $this->realField, $order);
  }

}

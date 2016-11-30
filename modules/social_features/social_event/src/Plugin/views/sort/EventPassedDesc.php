<?php

namespace Drupal\social_event\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\Date;

/**
 * Basic sort handler for passed events.
 *
 * @ViewsSort("event_passed_upcoming_sort")
 */
class EventPassedDesc extends Date {

  /**
   * Called to add the sort to a query.
   */
  public function query() {
    $order = ($this->view->exposed_data["{$this->realField}_op"] == '>=') ? 'ASC' : 'DESC';
    $this->query->addOrderBy($this->tableAlias, $this->realField, $order);
  }
}
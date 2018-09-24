<?php

namespace Drupal\social_private_message\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filters threads that are deleted for the current user.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("social_private_message_deleted_threads")
 */
class FilterDeletedThreads extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

  }

}

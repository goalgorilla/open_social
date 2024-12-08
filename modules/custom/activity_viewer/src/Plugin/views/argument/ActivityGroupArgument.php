<?php

namespace Drupal\activity_viewer\Plugin\views\argument;

use Drupal\Core\Database\Query\Condition;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\views\Plugin\views\query\Sql;

/**
 * Default implementation of the base argument plugin.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("activity_group_argument")
 */
class ActivityGroupArgument extends ArgumentPluginBase {

  /**
   * Set up the query for this argument.
   *
   * The argument sent may be found at $this->argument.
   */
  public function query($group_by = FALSE): void {
    $this->ensureMyTable();

    /** @var Sql $query */
    $query = $this->query;

    // \Drupal\views\Plugin\views\query\QueryPluginBase.
    $query->addTable('activity__field_activity_recipient_group');
    $query->addTable('activity__field_activity_entity');
    $query->addTable('activity__field_activity_destinations');

    $or_condition = new Condition('OR');

    // Group is a recipient.
    $or_condition->condition('activity__field_activity_recipient_group.field_activity_recipient_group_target_id', $this->argument, '=');

    $query->addWhere('activity_group_argument', $or_condition);

    $this->query = $query;

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    $cache_contexts = parent::getCacheContexts();

    // Since the Stream is different per url.
    if (!in_array('url', $cache_contexts)) {
      $cache_contexts[] = 'url';
    }

    return $cache_contexts;
  }

}

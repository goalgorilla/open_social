<?php
/**
 * @file
 * Activity Group Argument for Views.
 */

namespace Drupal\activity_viewer\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\ArgumentPluginBase;

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
  public function query($group_by = FALSE) {
    $this->ensureMyTable();

    // \Drupal\views\Plugin\views\query\QueryPluginBase.
    $this->query->addTable('activity__field_activity_recipient_group');
    $this->query->addTable('activity__field_activity_entity');
    $this->query->addTable('activity__field_activity_destinations');

    $or_condition = db_or();

    // Group is a recipient.
    $or_condition->condition('activity__field_activity_recipient_group.field_activity_recipient_group_target_id', $this->argument, '=');

    $this->query->addWhere('activity_group_argument', $or_condition);

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cache_contexts = parent::getCacheContexts();

    // Since the Stream is different per url.
    if (!in_array('url', $cache_contexts)) {
      $cache_contexts[] = 'url';
    }

    return $cache_contexts;
  }

}

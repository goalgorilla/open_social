<?php

namespace Drupal\activity_creator\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\ArgumentPluginBase;

/**
 * Default implementation of the base argument plugin.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("activity_profile_argument")
 */
class ActivityProfileArgument extends ArgumentPluginBase {

  /**
   * Set up the query for this argument.
   *
   * The argument sent may be found at $this->argument.
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();

    // \Drupal\views\Plugin\views\query\QueryPluginBase

    $this->query->addTable('activity__field_activity_recipient_user');

    $or_condition = db_or();

    $or_condition->condition('activity__field_activity_recipient_user.field_activity_recipient_user_target_id', $this->argument, '=');
    $or_condition->condition('activity.user_id', $this->argument, '=');

    $this->query->addWhere('activity_profile_argument', $or_condition);

    // @TODO posts created by this user: see PostAccountStream::query

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

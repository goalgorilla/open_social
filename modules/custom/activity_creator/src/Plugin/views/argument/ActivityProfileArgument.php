<?php
/**
 * @file
 * Activity Profile Argument for Views.
 */

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

    // \Drupal\views\Plugin\views\query\QueryPluginBase.
    $this->query->addTable('activity__field_activity_recipient_user');
    $this->query->addTable('activity__field_activity_entity');
    $this->query->addTable('activity__field_activity_destinations');

    $or_condition = db_or();

    // User is a recipient.
    $or_condition->condition('activity__field_activity_recipient_user.field_activity_recipient_user_target_id', $this->argument, '=');

    // Or posted by the user, but not on someone else his profile..
    // TODO Because of this set-up we have to use distinct. Not perfect.
    $by_user = db_and();
    $by_user->condition('activity_field_data.user_id', $this->argument, '=');
    $by_user->condition('activity__field_activity_recipient_user.field_activity_recipient_user_target_id', NULL, 'IS NULL');
    // TODO Add condition for posts in a group as well (do not show them).
    $or_condition->condition($by_user);

    $this->query->addWhere('activity_profile_argument', $or_condition);

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

<?php

namespace Drupal\social_post\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filters post based on visibility settings.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("post_visibility_access")
 */
class PostVisibilityAccess extends FilterPluginBase {

  /**
   *
   */
  public function adminSummary() {
  }

  /**
   *
   */
  protected function operatorForm(&$form, FormStateInterface $form_state) {
  }

  /**
   *
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * Currently use similar access as for the entity.
   *
   * Probably want to extend this to entity access based on the node grant
   * system when this is implemented.
   * See https://www.drupal.org/node/777578
   */
  public function query() {
    $account = $this->view->getUser();
    $this->query->addTable('post__field_visibility');

    $and_condition = db_and();
    $should_add_where_clause = FALSE;
    if (!$account->hasPermission('view public posts')) {
      $and_condition->condition('post__field_visibility.field_visibility_value', '1', '!=');
      $should_add_where_clause = TRUE;
    }
    if (!$account->hasPermission('view community posts')) {
      $and_condition->condition('post__field_visibility.field_visibility_value', '2', '!=');
      $should_add_where_clause = TRUE;
    }
    if ($should_add_where_clause) {
      $this->query->addWhere('visibility', $and_condition);
    }
  }

}

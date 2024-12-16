<?php

namespace Drupal\social_post\Plugin\views\filter;

use Drupal\Core\Database\Query\Condition;
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
   * {@inheritdoc}
   */
  public function adminSummary(): void {
  }

  /**
   * {@inheritdoc}
   */
  protected function operatorForm(&$form, FormStateInterface $form_state): void {
  }

  /**
   * {@inheritdoc}
   */
  public function canExpose(): false {
    return FALSE;
  }

  /**
   * Currently use similar access as for the entity.
   *
   * Probably want to extend this to entity access based on the node grant
   * system when this is implemented.
   * See https://www.drupal.org/node/777578
   */
  public function query(): void {
    /** @var \Drupal\views\Plugin\views\query\Sql $query */
    $query = $this->query;

    $account = $this->view->getUser();
    $query->addTable('post__field_visibility');

    $and_condition = new Condition('AND');
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
      $query->addWhere('visibility', $and_condition);
    }

    $this->query = $query;
  }

}

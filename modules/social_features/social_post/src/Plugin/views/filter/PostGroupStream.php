<?php

namespace Drupal\social_post\Plugin\views\filter;

use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filters post on a group stream.
 *
 * @todo Perhaps we should create an PostEntityStream instead.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("post_group_stream")
 */
class PostGroupStream extends FilterPluginBase {

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
  public function canExpose(): FALSE {
    return FALSE;
  }

  /**
   * Query for the activity stream on the group pages.
   */
  public function query(): void {

    /** @var \Drupal\views\Plugin\views\query\Sql $query */
    $query = $this->query;

    // Get the group.
    $group = \Drupal::routeMatch()->getParameter('group');

    // Visibility logic when visiting a post stream on group page:
    // - All the posts to the group by you and other users in the community.
    $query->addTable('post__field_visibility');
    $query->addTable('post__field_recipient_group');

    // Or posted to the group by the community.
    $recipient_condition = new Condition('AND');
    $recipient_condition->condition('post__field_visibility.field_visibility_value', '0', '=');
    $recipient_condition->condition('post__field_recipient_group.field_recipient_group_target_id', $group->id(), '=');

    $query->addWhere('visibility', $recipient_condition);

    $this->query = $query;
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

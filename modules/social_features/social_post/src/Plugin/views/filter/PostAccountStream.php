<?php

namespace Drupal\social_post\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filters post on my stream.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("post_account_stream")
 */
class PostAccountStream extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
  }

  /**
   * {@inheritdoc}
   */
  protected function operatorForm(&$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * Query for the activity stream on the account pages.
   */
  public function query() {

    // Profile user.
    $account_profile = \Drupal::routeMatch()->getParameter('user');

    // Visibility logic when visiting a post stream on account page:
    // - All the posts to community, public by the account user.
    // - All the posts to the user by other users in the community.
    // Same logic for users who are visiting another OR own profile.
    $this->query->addTable('post__field_visibility');
    $this->query->addTable('post__field_recipient_user');

    $or_condition = db_or();

    // Or posted by the user to the community.
    $public_community_condition = db_and();
    $public_community_condition->condition('post.user_id', $account_profile->id(), '=');
    $public_community_condition->condition('post__field_visibility.field_visibility_value', array('1', '2'), 'IN');
    $or_condition->condition($public_community_condition);

    // Or posted to the user by the community.
    $recipient_condition = db_and();
    $recipient_condition->condition('post__field_visibility.field_visibility_value', '0', '=');
    $recipient_condition->condition('post__field_recipient_user.field_recipient_user_target_id', $account_profile->id(), '=');
    $or_condition->condition($recipient_condition);

    $this->query->addWhere('visibility', $or_condition);
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

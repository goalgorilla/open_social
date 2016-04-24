<?php

/**
 * @file
 * Contains \Drupal\social_post\Plugin\views\filter\PostAccountStream.
 */

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

  public function adminSummary() {
  }

  protected function operatorForm(&$form, FormStateInterface $form_state) {
  }

  public function canExpose() {
    return FALSE;
  }

  /**
   * Query for the activity stream on the account pages.
   */
  public function query() {
    $account = $this->view->getUser();
    $account_profile = \Drupal::routeMatch()->getParameter('user');
    $my_profile = FALSE;
    if (isset($account_profile) && ($account_profile === $account->id() || (is_object($account_profile) &&  $account->id() === $account_profile->id()))) {
      $my_profile = TRUE;
    }

    $this->query->addTable('post__field_visibility');
    $this->query->addTable('post__field_recipient_user');

    $or_condition = db_or();

    if ($my_profile) {
      $public_community_condition = db_and();
      $public_community_condition->condition('post.user_id', $account->id(), '=');
      $public_community_condition->condition('post__field_visibility.field_visibility_value', array('1', '2'), 'IN');
      $or_condition->condition($public_community_condition);
    }
    $recipient_condition = db_and();
    $recipient_condition->condition('post__field_visibility.field_visibility_value', '0', '=');
    $recipient_condition->condition('post__field_recipient_user.field_recipient_user_target_id', $account_profile->id(), '=');
    $or_condition->condition($recipient_condition);
    $this->query->addWhere('visibility', $or_condition);
  }
}

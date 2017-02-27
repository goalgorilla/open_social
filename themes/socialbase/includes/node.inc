<?php

use Drupal\node\Entity\Node;
use Drupal\Core\Entity\Entity;

/**
 * Prepares variables for node templates.
 *
 * Default template: node.html.twig.
 *
 * Most themes use their own copy of node.html.twig. The default is located
 * inside "/core/modules/node/templates/node.html.twig". Look in there for the
 * full list of variables.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An array of elements to display in view mode.
 *   - node: The node object.
 *   - view_mode: View mode; e.g., 'full', 'teaser', etc.
 */
function socialbase_preprocess_node(&$variables) {
  $node = $variables['node'];
  $account = $node->getOwner();
  $variables['content_type'] = $node->bundle();

  // We get the group link to the node if there is one, will return NULL if not.
  $group_link = socialbase_group_link($node);
  if (!empty($group_link)) {
    $variables['group_link'] = $group_link;
  }

  // Display author information.
  if ($account) {
    // Author profile picture.
    $storage = \Drupal::entityTypeManager()->getStorage('profile');
    if (!empty($storage)) {
      $user_profile = $storage->loadByUser($account, 'profile');
      if ($user_profile) {
        $content = \Drupal::entityTypeManager()
          ->getViewBuilder('profile')
          ->view($user_profile, 'compact');
        $variables['author_picture'] = $content;
      }
    }

    // Author name.
    $username = array(
      '#theme' => 'username',
      '#account' => $account,
    );
    $variables['author'] = drupal_render($username);
  }

  if (isset($variables['elements']['#node']) && !isset($variables['created_date_formatted'])) {
    $variables['created_date_formatted'] = \Drupal::service('date.formatter')
      ->format($variables['elements']['#node']->getCreatedTime(), 'social_long_date');
  }

  // Get current node.
  $node = $variables['node'];
  // Get current user.
  $currentuser = \Drupal::currentUser();

  // Only add submitted data on teasers since we have the page hero block.
  if ($variables['view_mode'] === 'teaser') {

    // Not for AN..
    $is_anonymous = \Drupal::currentUser()->isAnonymous();
    if (!$is_anonymous && $variables['node']->id()) {
      // Only on Events & Topics.
      if ($variables['node']->getType() == 'event' || $variables['node']->getType() == 'topic') {
        // Add group name to the teaser (if it's part of a group).
        $group_content = \Drupal\group\Entity\GroupContent::loadByEntity($variables['node']);
        if (!empty($group_content)) {
          // It can only exist in one group. So we get the first pointer out of
          // the array that gets returned from loading GroupContent.
          $group = reset($group_content)->getGroup();

          if (!empty($group)) {
            $variables['content']['group_name'] = $group->label();
          }
        }
      }
    }

    $variables['display_submitted'] = TRUE;
  }

  if ($variables['view_mode'] === 'hero') {
    unset($variables['label']);
  }

  // Date formats.
  $date = $variables['node']->getCreatedTime();
  if ($variables['view_mode'] === 'small_teaser') {
    $variables['date'] = \Drupal::service('date.formatter')
      ->format($date, 'social_short_date');
  }
  // Teasers and activity stream.
  $teaser_view_modes = ['teaser', 'activity', 'activity_comment'];
  if (in_array($variables['view_mode'], $teaser_view_modes)) {
    $variables['date'] = \Drupal::service('date.formatter')
      ->format($date, 'social_medium_date');
  }

  // Content visibility.
  if ((isset($node->field_content_visibility)) && !$currentuser->isAnonymous()) {
    $node_visibility_value = $node->field_content_visibility->getValue();
    $content_visibility = reset($node_visibility_value);
    switch ($content_visibility['value']) {
      case 'community':
        $variables['visibility_icon'] = 'community';
        $variables['visibility_label'] = t('community');
        break;

      case 'public':
        $variables['visibility_icon'] = 'public';
        $variables['visibility_label'] = t('public');
        break;
    }
  }

  if ($node->status->value == NODE_NOT_PUBLISHED) {
    $variables['status_label'] = t('unpublished');
  }

  // Let's see if we can remove comments from the content and render them in a
  // separate content_below array.
  $comment_field_name = '';
  $variables['comment_field_name'] = '';

  // Check on our node if we have the comment type field somewhere.
  $fields_on_node = $node->getFieldDefinitions();
  foreach ($fields_on_node as $field) {
    if ($field->getType() == 'comment') {
      $comment_field_name = $field->getName();
    }
  }

  // Our node has a comment reference. Let's remove it from content array.
  $variables['below_content'] = array();
  if (!empty($comment_field_name)) {
    if (!empty($variables['content'][$comment_field_name])) {
      // Add it to our custom comments_section for the template purposes and
      // remove it.
      $variables['below_content'][$comment_field_name] = $variables['content'][$comment_field_name];
      unset($variables['content'][$comment_field_name]);
    }

    // If we have a comment and the status is OPEN or CLOSED we can render icon
    // for comment count, and add the comment count to the node.
    if ($node->$comment_field_name->status != \Drupal\comment\Plugin\Field\FieldType\CommentItemInterface::HIDDEN) {
      $comment_count = _socialbase_node_get_comment_count($node, $comment_field_name);
      $t_args = array(':num_comments' => $comment_count);
      $variables['below_content'][$comment_field_name]['#title'] = t('Comments (:num_comments)', $t_args);

      // If it's closed, we only show the comment section when there are
      // comments placed. Closed means we show comments but you are not able
      // to add any comments.
      if (($node->$comment_field_name->status == \Drupal\comment\Plugin\Field\FieldType\CommentItemInterface::CLOSED && $comment_count > 0) || $node->$comment_field_name->status == \Drupal\comment\Plugin\Field\FieldType\CommentItemInterface::OPEN) {
        $variables['comment_field_status'] = $comment_field_name;
        $variables['comment_count'] = $comment_count;
      }
    }
  }

  // If we have the like and dislike widget, lets try and get the count in our
  // node.
  if (!empty($variables['content']['like_and_dislike'])) {
    $variables['likes_count'] = _socialbase_node_get_like_count($variables['node']->getEntityTypeId(), $variables['node']->id());
  }

  //Add styles for nodes in preview
  if ($node->in_preview) {
    $variables['#attached']['library'][] = 'socialbase/preview';
  }
}

/**
 * Get comment count for a node.
 *
 * @param \Drupal\node\Entity\Node $node
 * @param $comment_field_name
 * @return int
 * @internal param $entity
 */
function _socialbase_node_get_comment_count(Node $node, $comment_field_name) {
  $count = 0;

  $comment_count = $node->{$comment_field_name}->comment_count;

  if ($comment_count) {
    $count = $comment_count;
  }

  return $count;
}

/**
 * Get like count for a node.
 *
 * @param $type entity type
 * @param $id entity id
 * @return int
 * @internal param $entity
 */
function _socialbase_node_get_like_count($type, $id) {
  $count = 0;

  if (!empty($type) && !empty($id)) {
    $manager = Drupal::service('plugin.manager.votingapi.resultfunction');
    $results = $manager->getResults($type, $id);

    if (!empty($results['like']['vote_sum'])) {
      $count = $results['like']['vote_sum'];
    }
  }

  return $count;
}

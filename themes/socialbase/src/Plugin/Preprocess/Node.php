<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\bootstrap\Utility\Element;
use Drupal\bootstrap\Utility\Variables;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\group\Entity\GroupContent;

/**
 * Pre-processes variables for the "node" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("node")
 */
class Node extends PreprocessBase {

  /**
   * {@inheritdoc}
   */
  protected function preprocessElement(Element $element, Variables $variables) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $variables['node'];
    $account = $node->getOwner();
    $variables['content_type'] = $node->bundle();

    // We get the group link to the node if there is one,
    // will return NULL if not.
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
      $username = [
        '#theme' => 'username',
        '#account' => $account,
      ];
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
          $group_content = GroupContent::loadByEntity($variables['node']);
          if (!empty($group_content)) {
            // It can only exist in one group.
            // So we get the first pointer out of
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

    // Date formats.
    $date = $variables['node']->getCreatedTime();
    if ($variables['view_mode'] === 'small_teaser') {
      $variables['date'] = \Drupal::service('date.formatter')
        ->format($date, 'social_short_date');
    }
    // Teasers and activity stream.
    $teaser_view_modes = ['teaser', 'activity', 'activity_comment', 'featured'];
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

        case 'group':
          $variables['visibility_icon'] = 'lock';
          $variables['visibility_label'] = t('group');
          break;
      }
    }

    if ($node->status->value == NODE_NOT_PUBLISHED) {
      $variables['status_label'] = t('unpublished');
    }

    // Content visibility for AN can be shown.
    // This is also used to render the shariff links for example.
    if ((isset($node->field_content_visibility)) &&
      ($variables['view_mode'] === 'full' || $variables['view_mode'] === 'hero') &&
      $currentuser->isAnonymous()) {
      $node_visibility_value = $node->field_content_visibility->getValue();
      $content_visibility = reset($node_visibility_value);
      if ($content_visibility['value'] === 'public') {
        $variables['visibility_icon'] = 'public';
      }
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
    $variables['below_content'] = [];
    if (!empty($comment_field_name)) {
      if (!empty($variables['content'][$comment_field_name])) {
        // Add it to our custom comments_section for the template purposes and
        // remove it.
        $variables['below_content'][$comment_field_name] = $variables['content'][$comment_field_name];
        unset($variables['content'][$comment_field_name]);
      }

      // If we have a comment and the status is
      // OPEN or CLOSED we can render icon for
      // comment count, and add the comment count to the node.
      if ($node->$comment_field_name->status != CommentItemInterface::HIDDEN) {
        $comment_count = _socialbase_node_get_comment_count($node, $comment_field_name);
        $variables['below_content'][$comment_field_name]['#title'] = $comment_count === 0 ? t('Be the first one to comment') : t('Comments');

        // If it's closed, we only show the comment section when there are
        // comments placed. Closed means we show comments but you are not able
        // to add any comments.
        if (($node->$comment_field_name->status == CommentItemInterface::CLOSED && $comment_count > 0) || $node->$comment_field_name->status == CommentItemInterface::OPEN) {
          $variables['comment_field_status'] = $comment_field_name;
          $variables['comment_count'] = $comment_count;
        }
      }
    }

    // If we have the like and dislike widget available
    // for this node, we can print the count even for Anonymous.
    $enabled_types = \Drupal::config('like_and_dislike.settings')->get('enabled_types');
    $variables['likes_count'] = NULL;
    if (isset($enabled_types['node']) && in_array($node->getType(), $enabled_types['node'])) {
      $variables['likes_count'] = _socialbase_node_get_like_count($node->getEntityTypeId(), $node->id());
    }

    // Add styles for nodes in preview.
    if ($node->in_preview) {
      $variables['#attached']['library'][] = 'socialbase/preview';
    }

    // For full view modes we render the links outside of the lazy builder so
    // we can render only subgroups of links.
    if ($variables['view_mode'] === 'full' && isset($variables['content']['links']['#lazy_builder'])) {
      // array_merge ensures other properties are kept (e.g. weight).
      $variables['content']['links'] = array_merge(
        $variables['content']['links'],
        call_user_func_array(
          $variables['content']['links']['#lazy_builder'][0],
          $variables['content']['links']['#lazy_builder'][1]
        )
      );
      unset($variables['content']['links']['#lazy_builder']);
    }

  }

}

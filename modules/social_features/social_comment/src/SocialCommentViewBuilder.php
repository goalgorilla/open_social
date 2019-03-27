<?php

namespace Drupal\social_comment;

use Drupal\comment\CommentViewBuilder;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * View builder handler for social comments.
 */
class SocialCommentViewBuilder extends CommentViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $comment, EntityViewDisplayInterface $display, $view_mode) {
    parent::alterBuild($build, $comment, $display, $view_mode);
    if (empty($comment->in_preview)) {
      $prefix = '';

      // Add indentation div or close open divs as needed.
      if ($build['#comment_threaded']) {
        if ($build['#comment_indent'] <= 0) {
          $prefix .= str_repeat('</div>', abs($build['#comment_indent']));
        }

        // We are in a thread of comments.
        if ($build['#comment_indent'] > 0) {
          $div_class = 'comments';

          // If the parent comment is unpublished, hide the thread for users
          // who may not see unpublished comments.
          if (!$comment->getParentComment()->isPublished() && !\Drupal::currentUser()->hasPermission('administer comments')) {
            $div_class .= ' hidden';
          }

          $prefix .= "\n" . '<div class="' . $div_class . '">';
        }
      }

      // Add anchor for each comment.
      $prefix .= "<a id=\"comment-{$comment->id()}\"></a>\n";
      $build['#prefix'] = $prefix;

      // Close all open divs.
      if (!empty($build['#comment_indent_final'])) {
        $build['#suffix'] = str_repeat('</div>', $build['#comment_indent_final']);
      }

      // Need to display reply comments without indentation in activity items.
      $no_indent_view_modes = [
        'activity',
        'activity_comment',
      ];
      if (in_array($view_mode, $no_indent_view_modes)) {
        $build['#prefix'] = '';
        $build['#suffix'] = '';
      }

    }
  }

}

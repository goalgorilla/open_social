<?php

namespace Drupal\social_comment;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\comment\CommentViewBuilder;

/**
 * View builder handler for social comments.
 */
class SocialCommentViewBuilder extends CommentViewBuilder {

  /**
   * The pager tag.
   */
  const PAGER_TAG = 'comments';

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $comment, EntityViewDisplayInterface $display, $view_mode) {
    parent::alterBuild($build, $comment, $display, $view_mode);

    /** @var \Drupal\comment\CommentInterface $comment */
    if (empty($comment->in_preview)) {
      // Need to display reply comments without indentation in activity items.
      $no_indent_view_modes = [
        'activity',
        'activity_comment',
      ];

      if (in_array($view_mode, $no_indent_view_modes)) {
        $build['#prefix'] = $build['#suffix'] = '';
        return;
      }

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
          if (
            !$comment->getParentComment()->isPublished() &&
            !$this->currentUser->hasPermission('administer comments')
          ) {
            $div_class .= ' hidden';
          }

          $prefix .= PHP_EOL . '<div class="' . $div_class . '">';
        }
      }

      // Add anchor for each comment.
      $prefix .= "<a id=\"comment-{$comment->id()}\"></a>\n";
      $build['#prefix'] = $prefix;

      // Close all open divs.
      if (!empty($build['#comment_indent_final'])) {
        $build['#suffix'] = str_repeat('</div>', $build['#comment_indent_final']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildMultiple(array $build_list) {
    $build_list = parent::buildMultiple($build_list);

    $tags = $build_list['pager']['#tags'] ?? [];
    $tags[] = self::PAGER_TAG;
    $build_list['pager']['#tags'] = $tags;

    return $build_list;
  }

}

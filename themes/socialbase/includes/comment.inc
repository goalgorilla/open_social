<?php

/**
 * @file
 * The comment inc file for the Social base theme.
 */

/**
 * Implements hook_theme_suggestions_comment_alter().
 */
function socialbase_theme_suggestions_comment_alter(array &$suggestions, array $variables) {
  $comment = $variables['elements']['#comment'];
  if ($comment->hasParentComment()) {
    $suggestions[] = 'comment__child';
  }
}

<?php

/**
 * Implements hook_theme_suggestions_comment_alter().
 * @param array $suggestions
 * @param array $variables
 */
function socialbase_theme_suggestions_comment_alter(array &$suggestions, array $variables) {
  $comment = $variables['elements']['#comment'];
  if ($comment->hasParentComment()) {
    $suggestions[] = 'comment__child';
  }
}

<?php

/**
 * @file
 * The book inc file for the Social base theme.
 */

/**
 * Implements hook_preprocess_book_navigation().
 */
function socialbase_preprocess_book_navigation(&$variables) {
  // Disables the menu tree below the content on a
  // book node in full display mode.
  $variables['tree'] = '';
}

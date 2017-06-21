<?php
/**
 * Implements hook_preprocess_book_navigation().
 * @param $variables
 */
function socialbase_preprocess_book_navigation(&$variables) {
  $variables['tree'] = ''; // Disables the menu tree below the content on a book node in full display mode.
}

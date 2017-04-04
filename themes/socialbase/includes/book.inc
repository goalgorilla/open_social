<?php
/**
 * Implements hook_preprocess_book_navigation().
 * @param $variables
 */
function socialbase_preprocess_book_navigation(&$variables) {
  $variables['tree'] = ''; // Disables the menu tree below the content on a book node in full display mode.

  // Find out what the active theme is first.
  $theme = \Drupal::theme()->getActiveTheme();

  // Check if socialbase is one of the base themes.
  // Then get the path to socialbase theme and provide a variable
  // that can be used in the template for a path to the icons.
  if (array_key_exists('socialbase', $theme->getBaseThemes())) {
    $basethemes = $theme->getBaseThemes();
    $variables['path_to_socialbase'] = $basethemes['socialbase']->getPath();
  }

}
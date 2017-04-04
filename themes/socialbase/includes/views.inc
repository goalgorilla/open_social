<?php

/**
 * Implements hook_preprocess_views_exposed_form().
 *
 * For all views exposed forms we set a consistent form actions layout.
 */
function socialbase_preprocess_views_exposed_form(&$variables) {
  // Find out what the active theme is first.
  $theme = \Drupal::theme()->getActiveTheme();

  // Check if socialbase is one of the base themes.
  // Then get the path to socialbase theme and provide a variable
  // that can be used in the template for a path to the icons.
  if (array_key_exists('socialbase', $theme->getBaseThemes())) {
    $basethemes = $theme->getBaseThemes();
    $variables['path_to_socialbase'] = $basethemes['socialbase']->getPath();
  }

  if (isset($variables['theme_hook_original']) && $variables['theme_hook_original'] === 'views_exposed_form') {
    $variables['form']['actions']['#exposed_form'] = TRUE;
    $variables['form']['actions']['submit']['#button_type'] = 'default';
    $variables['form']['actions']['reset']['#button_type'] = 'flat';
  }
}

/**
 * Implements hook_theme_suggestions_views_view_alter().
 */
function socialbase_theme_suggestions_views_view_alter(array &$suggestions, array $variables) {
  $view_id = $variables['view']->id();
  $views_display = $variables['view']->getDisplay();
  if (!empty($views_display->display['id']) && $views_display->display['id'] == 'view_enrollments') {
    $suggestions[] = $variables['theme_hook_original'] . '__page';
  }

  if (!empty($views_display->display['id']) && (
      $views_display->display['id'] == 'page_explore' ||
      $views_display->display['id'] == 'block_stream_homepage' ||
      $views_display->display['id'] == 'page_activity_stream' ||
      $view_id == 'activity_stream_profile' || $view_id == 'activity_stream_group')
  ) {
    $suggestions[] = $variables['theme_hook_original'] . '__stream';
  }
}

function socialbase_preprocess_views_view_unformatted(&$variables) {
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


function socialbase_preprocess_views_mini_pager(&$variables) {
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
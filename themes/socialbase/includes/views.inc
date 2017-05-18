<?php

/**
 * Implements hook_preprocess_views_exposed_form().
 *
 * For all views exposed forms we set a consistent form actions layout.
 */
function socialbase_preprocess_views_exposed_form(&$variables) {
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

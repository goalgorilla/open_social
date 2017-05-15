<?php

use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Implements hook_preprocess_container().
 */
function socialbase_preprocess_container(&$variables) {

  // For pages in search we would like to render containers without divs.
  $routename = \Drupal::request()
    ->get(RouteObjectInterface::ROUTE_NAME);
  if (strpos($routename, 'search') !== FALSE) {

    // Exclude the filter block on the search page
    if (!isset($variables['element']['#exposed_form'])) {
      $variables['bare'] = TRUE;
    }
  }

  // remove extra wrapper for container of comment files form
  if (isset($variables['element']['#id']) && $variables['element']['#id'] == 'edit-field-comment-files-wrapper') {
    $variables['bare'] = TRUE;
  };

  if (isset($variables['element']['#inline'])) {
    $variables['bare'] = TRUE;
  }

  if (isset($variables['element']['#type']) && $variables['element']['#type'] == 'view') {
    $variables['bare'] = TRUE;
  }

  // Identify the container used for search in the navbar. Var is set in hook_preprocess_block
  if (isset($variables['element']['#addsearchicon'])) {
    $variables['bare'] = TRUE;
  }

  // Identify the container used for views_exposed filter. Var is set in hook_preprocess_views_exposed_form
  if (isset($variables['element']['#exposed_form'])) {
    $variables['exposed_form'] = TRUE;
  }

}


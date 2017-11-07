<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Pre-processes variables for the "container" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("container")
 */
class Container extends PreprocessBase {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info) {
    parent::preprocess($variables, $hook, $info);

    // For pages in search we would like to render containers without divs.
    $routename = \Drupal::request()
      ->get(RouteObjectInterface::ROUTE_NAME);
    if (strpos($routename, 'search') !== FALSE) {

      // Exclude the filter block on the search page.
      if (!isset($variables['element']['#exposed_form'])) {
        $variables['bare'] = TRUE;
      }
    }

    // Remove extra wrapper for container of post image form.
    if (isset($variables['element']['#id']) && $variables['element']['#id'] == 'edit-field-comment-files-wrapper') {
      $variables['bare'] = TRUE;
    };

    if (isset($variables['element']['#inline'])) {
      $variables['bare'] = TRUE;
    }

    if (isset($variables['element']['#type']) && $variables['element']['#type'] == 'view') {
      $variables['bare'] = TRUE;
    }

    // Identify the container used for search in the nav bar.
    // Var is set in hook_preprocess_block.
    if (isset($variables['element']['#addsearchicon'])) {
      $variables['bare'] = TRUE;
    }

    // Identify the container used for views_exposed filter.
    // Var is set in hook_preprocess_views_exposed_form.
    if (isset($variables['element']['#exposed_form'])) {
      $variables['exposed_form'] = TRUE;
    }

  }

}

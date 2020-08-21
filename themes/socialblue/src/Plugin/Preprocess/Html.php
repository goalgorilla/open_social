<?php

namespace Drupal\socialblue\Plugin\Preprocess;

use Drupal\bootstrap\Utility\Variables;
use Drupal\socialbase\Plugin\Preprocess\Html as HtmlBase;

/**
 * Pre-processes variables for the "html" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("html")
 */
class Html extends HtmlBase {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info) {
    parent::preprocess($variables, $hook, $info);

    $variables['colors'] = [];

    foreach (color_get_palette($variables['theme']['name']) as $key => $value) {
      $key = str_replace('-', '_', $key);

      $variables['colors'][$key] = $value;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function preprocessVariables(Variables $variables) {
    // Add style class to html body.
    $style = theme_get_setting('style');
    if (!empty($style)) {
      $variables['attributes']['class'][] = 'socialblue--' . $style;
      $route_match = \Drupal::routeMatch();
      // For SKY when we are on edit user or edit profile
      // we want to distinct the root_path with a better class name
      // this is used in html.html.twig.
      if ($route_match->getParameter('user') &&
        $route_match->getRouteName() === 'profile.user_page.single' ||
        $route_match->getRouteName() === 'entity.user.edit_form') {
        $variables['root_path'] = 'user-edit';
      }
    }

    parent::preprocessVariables($variables);
  }

}

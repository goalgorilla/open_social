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
    }

    parent::preprocessVariables($variables);
  }

}

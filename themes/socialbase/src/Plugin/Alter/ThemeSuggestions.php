<?php
/**
 * @file
 * Contains \Drupal\socialbase\Plugin\Alter\ThemeSuggestions.
 */

namespace Drupal\socialbase\Plugin\Alter;

use Drupal\bootstrap\Utility\Variables;

/**
 * Implements hook_theme_suggestions_alter().
 *
 * @ingroup plugins_alter
 *
 * @BootstrapAlter("theme_suggestions")
 */
class ThemeSuggestions extends \Drupal\bootstrap\Plugin\Alter\ThemeSuggestions {

  /**
   * {@inheritdoc}
   */
  public function alter(&$suggestions, &$context1 = NULL, &$hook = NULL) {
    parent::alter($suggestions, $context1, $hook);

    $variables = Variables::create($context1);

    switch ($hook) {

      case 'details':
        $suggestions[] = 'details__plain';

        if (in_array('image-data__crop-wrapper', $variables['element']['#attributes']['class'])) {
          $suggestions[] = 'details__crop';
        }
        break;

      case 'file_link':

        // Gget the route name for file links
        $route_name = \Drupal::routeMatch()->getRouteName();

        // If the file link is on the node full page, suggest another template
        if ($route_name == 'entity.node.canonical') {
          $suggestions[] = 'file_link__node';
        }
        break;

    }



  }

}
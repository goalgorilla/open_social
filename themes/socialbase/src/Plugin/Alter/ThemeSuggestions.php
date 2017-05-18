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

        // Template suggestion for upload attachments in comments
        if (isset($variables['element']['#entity_type']) && $variables['element']['#entity_type'] == 'comment') {
          $suggestions[] = 'details__comment';
        }

        break;

      case 'file_link':

        // Get the route name for file links
        $route_name = \Drupal::routeMatch()->getRouteName();

        // If the file link is part of a node field, suggest another template
        if ($route_name == 'entity.node.canonical') {
          $file_id = $context1['file']->id();
          $node = \Drupal::routeMatch()->getParameter('node');
          $files = $node->get('field_files')->getValue();
          foreach($files as $file) {
            if ($file['target_id'] == $file_id) {
              $suggestions[] = 'file_link__card';
              break;
            }
          }
        }
        // If the file link is part of a group field, suggest another template
        if ($route_name == 'entity.group.canonical') {
          $suggestions[] = 'file_link__card';
        }
        break;

    }



  }

}
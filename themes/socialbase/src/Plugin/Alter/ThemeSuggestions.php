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

    if ($hook == 'details') {
      $suggestions[] = 'details__plain';

      if (in_array('image-data__crop-wrapper', $variables['element']['#attributes']['class'])) {
        $suggestions[] = 'details__crop';
      }

    }

  }

}
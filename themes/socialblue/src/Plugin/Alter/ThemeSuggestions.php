<?php

namespace Drupal\socialblue\Plugin\Alter;

use Drupal\socialbase\Plugin\Alter\ThemeSuggestions as BaseThemeSuggestions;

/**
 * Implements hook_theme_suggestions_alter().
 *
 * @ingroup plugins_alter
 *
 * @BootstrapAlter("theme_suggestions")
 */
class ThemeSuggestions extends BaseThemeSuggestions {

  /**
   * {@inheritdoc}
   */
  public function alter(&$suggestions, &$context1 = NULL, &$hook = NULL) {
    parent::alter($suggestions, $context1, $hook);

    $style = theme_get_setting('style');

    if (!empty($style) && !empty($suggestions)) {
      foreach ($suggestions as $suggestion) {
        $suggestions[] = $suggestion . '__' . $style;
      }
    }

  }

}

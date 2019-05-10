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
  public function alter(&$suggestions, &$context = NULL, &$hook = NULL) {
    parent::alter($suggestions, $context, $hook);

    $style = theme_get_setting('style');

    if (!empty($style)) {
      $variables = $this->variables;
      $style_suggestions = [];
      $style_suggestions[] = $variables['theme_hook_original'] . '__' . $style;

      if (!empty($suggestions)) {
        foreach ($suggestions as $suggestion) {
          $style_suggestions[] = $suggestion . '__' . $style;
        }
      }

      $suggestions = array_merge($suggestions, $style_suggestions);
    }

  }

}

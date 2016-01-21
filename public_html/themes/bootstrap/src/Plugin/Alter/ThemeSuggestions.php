<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Alter\ThemeSuggestions.
 */

namespace Drupal\bootstrap\Plugin\Alter;

use Drupal\bootstrap\Annotation\BootstrapAlter;
use Drupal\bootstrap\Plugin\PluginBase;
use Drupal\bootstrap\Utility\Element;
use Drupal\bootstrap\Utility\Unicode;

/**
 * Implements hook_theme_suggestions_alter().
 *
 * @BootstrapAlter("theme_suggestions")
 */
class ThemeSuggestions extends PluginBase implements AlterInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(&$suggestions, &$variables = NULL, &$hook = NULL) {
    switch ($hook) {
      case 'links':
        if (Unicode::strpos($variables['theme_hook_original'], 'links__dropbutton') !== FALSE) {
          // Handle dropbutton "subtypes".
          // @see \Drupal\bootstrap\Plugin\Prerender\Dropbutton::preRenderElement()
          if ($suggestion = Unicode::substr($variables['theme_hook_original'], 17)) {
            $suggestions[] = 'bootstrap_dropdown' . $suggestion;
          }
          $suggestions[] = 'bootstrap_dropdown';
        }
        break;

      case 'fieldset':
      case 'details':
        $suggestions[] = 'bootstrap_panel';
        break;

      case 'input':
        $element = Element::create($variables['element']);
        if ($element->isButton()) {
          if ($element->getProperty('dropbutton')) {
            $suggestions[] = 'input__button__dropdown';
          }
          else {
            $suggestions[] = $element->getProperty('split') ? 'input__button__split' : 'input__button';
          }
        }
        elseif (!$element->isType(['checkbox', 'hidden', 'radio'])) {
          $suggestions[] = 'input__form_control';
        }
        break;
    }
  }

}

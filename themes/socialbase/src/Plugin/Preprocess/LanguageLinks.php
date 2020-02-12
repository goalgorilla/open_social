<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\bootstrap\Utility\Variables;

/**
 * Pre-processes variables for the "links__language_block" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("links__language_block")
 */
class LanguageLinks extends PreprocessBase {

  /**
   * {@inheritdoc}
   */
  public function preprocessVariables(Variables $variables) {
    $variables['attributes']['class'][] = 'dropdown-menu';
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $language = \Drupal::languageManager()->getLanguage($langcode);
    $variables['heading']['text'] = $language->getName();
  }

}

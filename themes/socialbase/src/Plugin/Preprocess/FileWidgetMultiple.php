<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;

/**
 * Pre-processes variables for the "file_widget_multiple" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("file_widget_multiple")
 */
class FileWidgetMultiple extends PreprocessBase {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info) {
    parent::preprocess($variables, $hook, $info);

    // Remove duplicated ajax wrapper for topic/events files field,
    // because one is already rendered in container.html.twig.
    if (!empty($variables['element']['#id']) && (strpos($variables['element']['#id'], 'edit-field-files') !== FALSE)) {
      unset($variables['element']['#prefix']);
      unset($variables['element']['#suffix']);
    }

  }

}

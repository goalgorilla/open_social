<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\Table;
use Drupal\bootstrap\Utility\Variables;

/**
 * Pre-processes variables for the "table" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("table")
 */
class SocialBaseTable extends Table {

  /**
   * {@inheritdoc}
   */
  public function preprocessVariables(Variables $variables) {
    if (isset($variables['attributes']['id']) && strpos($variables['attributes']['id'], 'edit-field-files') !== FALSE) {
      $variables['attributes']['class'][] = 'tablesaw';
      $variables['attributes']['data-tablesaw-mode'] = 'stack';
    }

    parent::preprocessVariables($variables);
  }

}

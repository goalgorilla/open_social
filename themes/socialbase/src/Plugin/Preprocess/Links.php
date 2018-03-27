<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\bootstrap\Utility\Variables;

/**
 * Pre-processes variables for the "links" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("links")
 */
class Links extends PreprocessBase {

  /**
   * {@inheritdoc}
   */
  public function preprocessVariables(Variables $variables) {
    unset($variables['links']['comment-add']);
    unset($variables['links']['comment-comments']);
  }

}

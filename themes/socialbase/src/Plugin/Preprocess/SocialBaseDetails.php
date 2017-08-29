<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;

/**
 * Pre-processes variables for the "details" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("details")
 */
class SocialBaseDetails extends PreprocessBase {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info) {

    // Do not display the details title in file upload widget.
    if (isset($variables['element']['#theme']) && $variables['element']['#theme'] == 'file_widget_multiple') {
      $variables['title'] = FALSE;
    }

  }

}

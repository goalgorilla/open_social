<?php

namespace Drupal\socialblue\Plugin\Preprocess;

use Drupal\bootstrap\Utility\Variables;
use Drupal\socialbase\Plugin\Preprocess\Links as LinksBase;

/**
 * Pre-processes variables for the "links" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("links")
 */
class Links extends LinksBase {

  /**
   * {@inheritdoc}
   */
  public function preprocessVariables(Variables $variables) {

    // Add default button style to read more links on node teasers.
    if ($variables['theme_hook_original'] === 'links__node') {
      $style = theme_get_setting('style');
      if ($style === 'sky' && isset($variables['links']['node-readmore']['link']['#options'])) {
        $variables['links']['node-readmore']['link']['#options']['attributes']['class'][] = 'btn btn-default';
      }
    }
  }

}

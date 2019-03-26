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

    // Move CSS class to "li" tag for marking a no-link item as disabled.
    foreach ($variables['links'] as &$link) {
      if (isset($link['text_attributes'])) {
        /** @var \Drupal\Core\Template\Attribute $text_attributes */
        $text_attributes = $link['text_attributes'];

        if ($link['disabled'] = $text_attributes->hasClass('disabled')) {
          /** @var \Drupal\Core\Template\Attribute $attributes */
          $attributes = $link['attributes'];

          $attributes->addClass('disabled');
          $text_attributes->removeClass('disabled');
        }
      }
    }
  }

}

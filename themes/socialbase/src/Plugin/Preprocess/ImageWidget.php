<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\ImageWidget as BaseImageWidget;
use Drupal\bootstrap\Utility\Element;
use Drupal\bootstrap\Utility\Variables;

/**
 * Pre-processes variables for the "image_widget" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @see image-widget.html.twig
 *
 * @BootstrapPreprocess("image_widget",
 *   replace = "template_preprocess_image_widget"
 * )
 */
class ImageWidget extends BaseImageWidget {

  /**
   * {@inheritdoc}
   */
  public function preprocessElement(Element $element, Variables $variables) {

    if (isset($variables['element']['#id']) &&  $variables['element']['#id'] == 'edit-field-post-image-0-upload') {
      $variables['in_post'] = TRUE;
    }

    if (isset($variables['data']['remove_button'])) {
      $variables['data']['remove_button']['#button_type'] = 'flat';
    }

    parent::preprocessElement($element, $variables);
  }

}

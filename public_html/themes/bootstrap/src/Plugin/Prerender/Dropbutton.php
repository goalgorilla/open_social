<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Prerender\Dropbutton.
 */

namespace Drupal\bootstrap\Plugin\Prerender;

use Drupal\bootstrap\Annotation\BootstrapPrerender;
use Drupal\bootstrap\Utility\Element;

/**
 * Defines the interface for an object oriented preprocess plugin.
 *
 * @BootstrapPrerender("dropbutton",
 *   replace = "Drupal\Core\Render\Element\Dropbutton::preRenderDropbutton"
 * )
 *
 * @see \Drupal\Core\Render\Element\Dropbutton::preRenderDropbutton()
 */
class Dropbutton extends PrerenderBase {

  /**
   * {@inheritdoc}
   */
  public static function preRenderElement(Element $element) {
    $element['#attached']['library'][] = 'bootstrap/dropdown';

    // Enable targeted theming of specific dropbuttons (e.g., 'operations' or
    // 'operations__node').
    if ($subtype = $element->getProperty('subtype')) {
      $element->setProperty('theme', $element->getProperty('theme') . "__$subtype");
    }
  }

}

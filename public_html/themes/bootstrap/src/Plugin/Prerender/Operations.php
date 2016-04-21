<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Prerender\Operations.
 */

namespace Drupal\bootstrap\Plugin\Prerender;

use Drupal\bootstrap\Annotation\BootstrapPrerender;

/**
 * Pre-render callback for the "operations" element type.
 *
 * @BootstrapPrerender("operations",
 *   replace = "Drupal\Core\Render\Element\Operations::preRenderDropbutton"
 * )
 *
 * @see \Drupal\bootstrap\Plugin\Prerender\Dropbutton
 * @see \Drupal\Core\Render\Element\Operations::preRenderDropbutton()
 */
class Operations extends Dropbutton {}

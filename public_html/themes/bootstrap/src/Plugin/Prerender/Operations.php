<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Prerender\Operations.
 */

namespace Drupal\bootstrap\Plugin\Prerender;

use Drupal\bootstrap\Annotation\BootstrapPrerender;

/**
 * Defines the interface for an object oriented preprocess plugin.
 *
 * @BootstrapPrerender("operations",
 *   replace = "Drupal\Core\Render\Element\Operations::preRenderDropbutton"
 * )
 *
 * @see \Drupal\bootstrap\Plugin\Prerender\Dropbutton
 * @see \Drupal\Core\Render\Element\Operations::preRenderDropbutton()
 */
class Operations extends Dropbutton {}

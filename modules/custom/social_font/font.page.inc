<?php

/**
 * @file
 * Contains font.page.inc.
 *
 * Page callback for Font entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Font templates.
 *
 * Default template: font.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_font(array &$variables) {
  // Fetch Font Entity Object.
  $font = $variables['elements']['#font'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}

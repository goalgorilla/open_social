<?php

/**
 * @file
 * Preprocess functions for the Social Group module.
 */

use Drupal\Core\Template\Attribute;

/**
 * Prepares variables for join templates.
 *
 * Default template: join.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - attributes: An array of HTML attributes to apply to the wrapper.
 *   - primary: The primary element.
 *   - secondaries: The secondary elements.
 */
function template_preprocess_join(array &$variables): void {
  $variables['attributes'] = new Attribute($variables['attributes']);
}

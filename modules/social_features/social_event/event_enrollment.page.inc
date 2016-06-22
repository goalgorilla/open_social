<?php

/**
 * @file
 * Contains event_enrollment.page.inc..
 *
 * Page callback for Event enrollment entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Event enrollment templates.
 *
 * Default template: event_enrollment.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_event_enrollment(array &$variables) {
  // Fetch EventEnrollment Entity Object.
  $event_enrollment = $variables['elements']['#event_enrollment'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}

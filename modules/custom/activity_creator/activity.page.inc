<?php

/**
 * @file
 * Contains activity.page.inc..
 *
 * Page callback for Activity entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Activity templates.
 *
 * Default template: activity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_activity(array &$variables) {
  // Fetch Activity Entity Object.
  $activity = $variables['elements']['#activity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}

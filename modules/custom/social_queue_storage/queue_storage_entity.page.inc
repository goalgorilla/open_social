<?php

/**
 * @file
 * Contains queue_storage_entity.page.inc.
 *
 * Page callback for Queue storage entity entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Queue storage entity templates.
 *
 * Default template: queue_storage_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_queue_storage_entity(array &$variables) {
  // Fetch QueueStorageEntity Entity Object.
  $queue_storage_entity = $variables['elements']['#queue_storage_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}

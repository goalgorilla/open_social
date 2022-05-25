<?php

/**
 * @file
 * Preprocess functions for the Social Event module.
 */

use Drupal\Core\Template\Attribute;
use Drupal\node\NodeInterface;

/**
 * Prepares variables for join templates.
 *
 * Default template: event-date-advanced.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - attributes: An array of HTML attributes to apply to the wrapper.
 *   - primary: The primary element.
 *   - secondaries: The secondary elements.
 */
function template_preprocess_event_date_advanced(array &$variables): void {
  $variables['attributes'] = (new Attribute($variables['attributes']))
    ->addClass('event-date-advanced');

  // Recount date for event.
  $node = $variables['node'];
  $view_mode = $variables['view_mode'];
  if ($node instanceof NodeInterface) {
    $variables['date'] = _social_event_format_date($node, $view_mode);
  }

  // Add an icon.
  $module_path = \Drupal::service('extension.list.module')
    ->getPath('social_event');

  $variables['icon'] = "/{$module_path}/assets/icons/time.svg";

  $variables['#attached']['library'][] = 'social_event/event';
  $variables['#cache']['contexts'][] = 'timezone';
}

/**
 * Prepares variables for join templates.
 *
 * Default template: event-location-address.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - attributes: An array of HTML attributes to apply to the wrapper.
 *   - primary: The primary element.
 *   - secondaries: The secondary elements.
 */
function template_preprocess_event_location_address(array &$variables): void {
  $variables['attributes'] = (new Attribute($variables['attributes']))
    ->addClass('event-location-address');

  $node = $variables['node'];

  if ($node instanceof NodeInterface) {
    $variables['location'] = !$node->get('field_event_location')->isEmpty()
      ? $node->get('field_event_location')->getString()
      : NULL;
    $variables['address'] = !$node->get('field_event_address')->isEmpty()
      ? $node->get('field_event_address')->getString()
      : NULL;
  }

  // Add an icon.
  $module_path = \Drupal::service('extension.list.module')
    ->getPath('social_event');

  $variables['icon'] = "/{$module_path}/assets/icons/location.svg";

  $variables['#attached']['library'][] = 'social_event/event';
}

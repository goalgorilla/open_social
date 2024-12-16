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

  // We want to use icon from the loaded file rather than embed it. Because
  // it causes performance issue on some pages (event listing, for example).
  // Conversely, in the email we can't get svg icons by id because the file
  // with svg bulk can't be loaded (so, we embed icon directly in the template).
  $variables['icon_loaded'] = $variables['view_mode'] !== 'email_card';

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

    $variables['address'] = NULL;
    if (!$node->get('field_event_address')->isEmpty()) {
      $country_list = \Drupal::service('address.country_repository')->getList();
      $address_value = current($node->get('field_event_address')->getValue());
      $address_sequence = ['address_line1', 'address_line2', 'address_line3', 'locality', 'country_code'];

      $address = [];
      foreach ($address_sequence as $address_key) {
        $address[] = match ($address_key) {
          'locality' => is_null($address_value['postal_code'])
            ? $address_value[$address_key]
            : sprintf('%s %s', $address_value['postal_code'], $address_value[$address_key]),
          'country_code' => $country_list[$address_value[$address_key]],
          default => $address_value[$address_key],
        };
      }

      $variables['address'] = trim(implode(', ', array_filter($address)), ", ");
    }
  }

  // We want to use icon from the loaded file rather than embed it. Because
  // it causes performance issue on some pages (event listing, for example).
  // Conversely, in the email we can't get svg icons by id because the file
  // with svg bulk can't be loaded (so, we embed icon directly in the template).
  $variables['icon_loaded'] = $variables['view_mode'] !== 'email_card';

  $variables['#attached']['library'][] = 'social_event/event';
}

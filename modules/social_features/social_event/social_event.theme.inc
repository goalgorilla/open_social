<?php

/**
 * @file
 * Preprocess functions for the Social Event module.
 */

use Drupal\Core\Template\Attribute;
use Drupal\node\NodeInterface;
use Drupal\social_event\Entity\Node\EventInterface;
use Drupal\social_event\Service\EventOnline;

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
    $variables['address'] = !$node->get('field_event_address')->isEmpty()
      ? $node->get('field_event_address')->getString()
      : NULL;
  }

  // We want to use icon from the loaded file rather than embed it. Because
  // it causes performance issue on some pages (event listing, for example).
  // Conversely, in the email we can't get svg icons by id because the file
  // with svg bulk can't be loaded (so, we embed icon directly in the template).
  $variables['icon_loaded'] = $variables['view_mode'] !== 'email_card';

  $variables['#attached']['library'][] = 'social_event/event';
}

/**
 * Prepares variables for the online flag.
 *
 * Default template: event-flag-online.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - attributes: An array of HTML attributes to apply to the wrapper.
 *   - primary: The primary element.
 *   - secondaries: The secondary elements.
 */
function template_preprocess_event_flag_online(array &$variables): void {
  $event = $variables['node'];
  if (!$event instanceof EventInterface) {
    return;
  }

  /** @var \Drupal\social_event\Service\EventOnline $eos */
  $eos = \Drupal::service(EventOnline::class);

  $variables['attributes'] = (new Attribute($variables['attributes']))
    ->addClass('event-flag-online');

  if ($meeting = $event->getMeeting()) {
    $variables['attributes']
      ->setAttribute('title', $eos->getTooltipText($meeting->bundle()));
  }

  $variables['online'] = $event->isOnline();

  // We want to use icon from the loaded file rather than embed it. Because
  // it causes performance issue on some pages (event listing, for example).
  // Conversely, in the email we can't get svg icons by id because the file
  // with svg bulk can't be loaded (so, we embed icon directly in the template).
  $variables['icon_loaded'] = $variables['view_mode'] !== 'email_card';

  $variables['#attached']['library'][] = 'social_event/event';
}

/**
 * Prepares variables for the enrolled flag.
 *
 * Default template: event-flag-enrolled.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - attributes: An array of HTML attributes to apply to the wrapper.
 *   - primary: The primary element.
 *   - secondaries: The secondary elements.
 */
function template_preprocess_event_flag_enrolled(array &$variables): void {
  $event = $variables['node'];
  if (!$event instanceof EventInterface) {
    return;
  }

  $variables['attributes'] = (new Attribute($variables['attributes']))
    ->addClass('event-flag-enrolled')
    ->setAttribute('title', t('You are enrolled in this event'));

  $variables['is_enrolled'] = $event->getParticipation();

  // We want to use icon from the loaded file rather than embed it. Because
  // it causes performance issue on some pages (event listing, for example).
  // Conversely, in the email we can't get svg icons by id because the file
  // with svg bulk can't be loaded (so, we embed icon directly in the template).
  $variables['icon_loaded'] = $variables['view_mode'] !== 'email_card';

  $variables['#attached']['library'][] = 'social_event/event';
}

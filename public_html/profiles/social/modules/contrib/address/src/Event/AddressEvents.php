<?php

/**
 * @file
 * Contains \Drupal\address\Event\AddressEvents.
 */

namespace Drupal\address\Event;

/**
 * Defines events for the address module.
 */
final class AddressEvents {

  /**
   * Name of the event fired when altering the list of available countries.
   *
   * @Event
   *
   * @see \Drupal\address\Event\AvailableCountriesEvent
   */
  const AVAILABLE_COUNTRIES = 'address.available_countries';

  /**
   * Name of the event fired when altering initial values.
   *
   * @Event
   *
   * @see \Drupal\address\Event\InitialValuesEvent
   */
  const INITIAL_VALUES = 'address.widget.initial_values';

}

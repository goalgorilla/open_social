<?php

/**
 * @file
 * Contains \Drupal\address_test\EventSubscriber\AddressTestEventSubscriber.
 */

namespace Drupal\address_test\EventSubscriber;

use Drupal\address\Event\AddressEvents;
use Drupal\address\Event\AvailableCountriesEvent;
use Drupal\address\Event\InitialValuesEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AddressTestEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AddressEvents::AVAILABLE_COUNTRIES][] = ['onAvailableCountries'];
    $events[AddressEvents::INITIAL_VALUES][] = ['onInitialValues'];
    return $events;
  }

  /**
   * Generate a set of available countries.
   *
   * @return array Array of counries.
   */
  public function getAvailableCountries() {
    return ['AU' => 'AU', 'BR' => 'BR', 'CA' => 'CA', 'FR' => 'FR', 'JP' => 'JP'];
  }

  /**
   * Generate a set of initial values.
   *
   * @return array Array of initial values.
   */
  public function getInitialValues() {
    return [
      'country_code' => 'AU',
      'administrative_area' => 'AU-NSW',
      'locality' => 'Sydney',
      'dependent_locality' => '',
      'postal_code' => '2000',
      'sorting_code' => '',
      'address_line1' => 'Some address',
      'address_line2' => 'Some street',
      'organization' => 'Some Organization',
      'recipient' => 'Some Recipient',
    ];
  }

  /**
   * Set available countries in the available countries event.
   *
   * @param \Drupal\address\Event\AvailableCountriesEvent $event
   */
  public function onAvailableCountries(AvailableCountriesEvent $event) {
    $event->setAvailableCountries($this->getAvailableCountries());
  }

  /**
   * Set initial values in the initial values event.
   *
   * @param \Drupal\address\Event\InitialValuesEvent $event
   */
  public function onInitialValues(InitialValuesEvent $event) {
    $event->setInitialValues($this->getInitialValues());
  }

}

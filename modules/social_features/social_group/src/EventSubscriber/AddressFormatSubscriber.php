<?php

namespace Drupal\social_group\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\address\Event\AddressEvents;

/**
 * Class AddressFormatSubscriber.
 *
 * @package Drupal\social_group\EventSubscriber
 */
class AddressFormatSubscriber implements EventSubscriberInterface {

  /**
   * Get the subscribed events.
   *
   * @return mixed
   *   Returns the subscribed events.
   */
  public static function getSubscribedEvents() {
    $events[AddressEvents::ADDRESS_FORMAT][] = ['onGetDefinition', 0];
    return $events;
  }

  /**
   * The onGetDefinition function.
   */
  public function onGetDefinition($event) {
    $definition = $event->getDefinition();
    // This makes all address fields optional for all entity types on site.
    // We can't set empty array because of check in AddressFormat.php, line 128.
    $definition['required_fields'] = ['givenName'];
    $event->setDefinition($definition);
  }

}

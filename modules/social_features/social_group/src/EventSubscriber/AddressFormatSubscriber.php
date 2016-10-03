<?php

/**
 * @file
 * Contains Drupal\social_group\EventSubscriber\AddressFormatSubscriber.
 */

namespace Drupal\social_group\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\address\Event\AddressEvents;

class AddressFormatSubscriber implements EventSubscriberInterface {

  static function getSubscribedEvents() {
    $events[AddressEvents::ADDRESS_FORMAT][] = array('onGetDefinition', 0);
    return $events;
  }

  public function onGetDefinition($event) {
    $definition = $event->getDefinition();
    // This makes all address fields optional for all entity types on site.
    // We can't set empty array because of check in AddressFormat.php, line 128.
    $definition['required_fields'] = ['givenName'];
    $event->setDefinition($definition);
  }

}
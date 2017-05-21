<?php

/**
 * @file
 * Contains Drupal\social_group\EventSubscriber\AddressFormatSubscriber.
 */

namespace Drupal\social_group\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\address\Event\AddressEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AddressFormatSubscriber implements EventSubscriberInterface {

  static function getSubscribedEvents() {
    $events[AddressEvents::ADDRESS_FORMAT][] = array('onGetDefinition', 0);
    $events[KernelEvents::REQUEST][] = array('checkForRedirection');
    return $events;
  }

  public function onGetDefinition($event) {
    $definition = $event->getDefinition();
    // This makes all address fields optional for all entity types on site.
    // We can't set empty array because of check in AddressFormat.php, line 128.
    $definition['required_fields'] = ['givenName'];
    $event->setDefinition($definition);
  }

  /**
   * This method is called whenever the KernelEvents::REQUEST event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function checkForRedirection(GetResponseEvent $event) {
    // Check if there is a group object on the current route.
    $group = _social_group_get_current_group();
    // Get the current route name for the checks being performed below
    $routeMatch = \Drupal::routeMatch()->getRouteName();
    // Get the current user
    $user = \Drupal::currentUser();
    // The array of forbidden routes.
    $routes = [
      'entity.group.canonical',
      'entity.group.join',
      'view.group_events.page_group_events',
      'view.group_topics.page_group_topics',
    ];
    if ($user->id() != 1) {
      // If a group is set, and the type is closed_group
      if ($group && $group->getGroupType()->id() == 'closed_group') {
      // Check if the user meets the conditions, then perform a redirect if needed.
        if (!$group->getMember($user) && in_array($routeMatch, $routes)){
        $event->setResponse(new RedirectResponse(Url::fromRoute('view.group_information.page_group_about', ['group' => $group->id()])
          ->toString()));
        }
      }
    }
  }
}

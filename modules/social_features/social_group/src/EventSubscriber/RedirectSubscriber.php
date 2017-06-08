<?php

namespace Drupal\social_group\EventSubscriber;


use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RedirectSubscriber implements EventSubscriberInterface {

  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('checkForRedirection');
    return $events;
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
    // If a group is set, and the type is closed_group
    if ($group && $group->getGroupType()->id() == 'closed_group') {
      if ($user->id() != 1) {
        // The roles allowed to access and edit closed groups
        $allowed_roles = [
          'contentmanager',
          'sitemanager'
        ];
        $allowed = FALSE;
        foreach ($allowed_roles as $role) {
          if (in_array($role, $user->getRoles())) {
            $allowed = TRUE;
            return;
          }
          // If the user is not an member of this group
          elseif (!$group->getMember($user) && in_array($routeMatch, $routes)) {
            $event->setResponse(new RedirectResponse(Url::fromRoute('view.group_information.page_group_about', ['group' => $group->id()])
              ->toString()));
          }
        }
      }
    }
  }
}
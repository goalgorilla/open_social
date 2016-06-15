<?php
/**
 * MarkNotificationsAsRead event subscriber.
 */

namespace Drupal\activity_creator\EventSubscriber;

// This is the interface we are going to implement.
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
// This class contains the event we want to subscribe to.
use Symfony\Component\HttpKernel\KernelEvents;
// Our event listener method will receive one of these.
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Subscribe to KernelEvents::REQUEST events and mark notifications as read.
 */
class MarkNotificationsAsRead implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('markNotificationsAsRead');
    return $events;
  }

  /**
   * This method is called whenever the KernelEvents::REQUEST event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function markNotificationsAsRead(GetResponseEvent $event) {
    // TODO Mark notifications for current user as read if we are on entity page
    // $request = $event->getRequest();
    // $account = \Drupal::currentUser();
  }
}
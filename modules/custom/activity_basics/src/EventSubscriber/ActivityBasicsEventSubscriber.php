<?php

namespace Drupal\activity_basics\EventSubscriber;

use Drupal\social_core\Event\EntityPublishedEvent;
use Drupal\social_core\Event\SocialCoreEventsInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The activity basics event subscriber.
 */
class ActivityBasicsEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[SocialCoreEventsInterface::PUBLISHED][] = ['onEntityPublished'];
    return $events;
  }

  /**
   * Listen the entity publishing event.
   *
   * @param \Drupal\social_core\Event\EntityPublishedEvent $event
   *   The event to process.
   */
  public function onEntityPublished(EntityPublishedEvent $event): void {
    _activity_basics_entity_action($event->getEntity(), 'publish_entity_action');
  }

}

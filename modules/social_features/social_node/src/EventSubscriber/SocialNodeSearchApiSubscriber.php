<?php

declare(strict_types=1);

namespace Drupal\social_node\EventSubscriber;

use Drupal\search_api\Event\GatheringPluginInfoEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Hide "Content Access" search api processor from UI.
 */
class SocialNodeSearchApiSubscriber implements EventSubscriberInterface {

  /**
   * Hide "Content Access" search api processor.
   *
   * @param \Drupal\search_api\Event\GatheringPluginInfoEvent $event
   *   The processor plugin info alters event.
   */
  public function hideContentAccessProcessor(GatheringPluginInfoEvent $event): void {
    $processor_info = &$event->getDefinitions();
    if (!empty($processor_info['content_access'])) {
      $processor_info['content_access']['hidden'] = 'true';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Workaround to avoid a fatal error during site install in some cases.
    // @see https://www.drupal.org/project/facets/issues/3199156
    if (!class_exists(SearchApiEvents::class)) {
      return [];
    }

    return [
      SearchApiEvents::GATHERING_PROCESSORS => 'hideContentAccessProcessor',
    ];
  }

}

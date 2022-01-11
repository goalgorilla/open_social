<?php

namespace Drupal\social_search\EventSubscriber;

use Drupal\search_api\Event\MappingViewsHandlersEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Search API events subscriber.
 */
class SearchApiSubscriber implements EventSubscriberInterface {

  /**
   * Adds the mapping to replace date filter with social_date_filter.
   *
   * @param \Drupal\search_api\Event\MappingViewsHandlersEvent $event
   *   The Search API event.
   */
  public function onMappingViewsFilterHandlers(MappingViewsHandlersEvent $event): void {
    $mapping = &$event->getHandlerMapping();

    // Override the Search API views filter connected to date with
    // SocialDate.php (Extends current one limits options).
    // @see Drupal\social_search\Plugin\views\filter\SocialDate.
    if (!empty($mapping['date']['filter']['id']) && $mapping['date']['filter']['id'] === 'search_api_date') {
      $mapping['date']['filter']['id'] = 'social_date_filter';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Workaround to avoid a fatal error during site install in some cases.
    // @see https://www.drupal.org/project/facets/issues/3199156
    if (!class_exists('\Drupal\search_api\Event\SearchApiEvents', TRUE)) {
      return [];
    }

    return [
      SearchApiEvents::MAPPING_VIEWS_HANDLERS => 'onMappingViewsFilterHandlers',
    ];

  }

}

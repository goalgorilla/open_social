<?php

namespace Drupal\social_topic\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\social_topic\Routing
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {

    // Override default title for the latest topics view.
    if ($route = $collection->get('view.latest_topics.page_latest_topics')) {
      $defaults = $route->getDefaults();
      $defaults['_title_callback'] = '\Drupal\social_topic\Controller\SocialTopicController::latestTopicsPageTitle';
      $route->setDefaults($defaults);
    }

  }

}

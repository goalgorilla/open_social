<?php

namespace Drupal\social_gdpr\Subscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\social_gdpr\Controller\GdprDataPolicy;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class Route.
 *
 * @package Drupal\social_gdpr\Subscriber
 */
class Route extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('data_policy.data_policy')) {
      $route->setRequirement('_custom_access', GdprDataPolicy::class . '::entityOverviewAccess');
    }
  }

}

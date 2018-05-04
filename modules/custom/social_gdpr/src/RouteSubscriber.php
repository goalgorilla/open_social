<?php

namespace Drupal\social_gdpr;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\social_gdpr
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('gdpr_consent.data_policy')) {
      $route->setRequirement('_custom_access', '\Drupal\social_gdpr\Controller\DataPolicy::entityOverviewAccess');
    }
  }

}

<?php

namespace Drupal\social_group_invite\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\social_group_invite\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('ginvite.invitation.bulk')) {
      $defaults = $route->getDefaults();
      $defaults['_form'] = '\Drupal\social_group_invite\Form\SocialBulkGroupInvitation';
      $route->setDefaults($defaults);
    }
  }

}

<?php

/**
 * @file
 * Contains \Drupal\social_user\Routing\RouteSubscriber.
 */

namespace Drupal\social_user\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\social_user\Routing
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Set route for the login to point to the SocialUserLoginForm.
    if ($route = $collection->get('user.login')) {
      $route->setDefaults(array(
        '_form' => '\Drupal\social_user\Form\SocialUserLoginForm',
        '_title' => 'Log in',
      ));
    }
    if ($route = $collection->get('user.pass')) {
      $route->setDefaults(array(
        '_form' => '\Drupal\social_user\Form\SocialUserPasswordForm',
        '_title' => 'Reset your password',
      ));
    }
  }
}

<?php

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
        '_title' => t('Log in')->render(),
      ));
    }
    if ($route = $collection->get('user.pass')) {
      $route->setDefaults(array(
        '_form' => '\Drupal\social_user\Form\SocialUserPasswordForm',
        '_title' => t('Reset your password')->render(),
      ));
    }
    // Route the user view page to user/{uid}/timeline.
    if ($route = $collection->get('entity.user.canonical')) {
      $route->setPath('/user/{user}/stream');
      $defaults = $route->getDefaults();
      $defaults['_title_callback'] = '\Drupal\social_user\Controller\SocialUserController::setUserStreamTitle';
      $route->setDefaults($defaults);
    }
  }

}

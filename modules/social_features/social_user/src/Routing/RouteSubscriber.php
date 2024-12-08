<?php

namespace Drupal\social_user\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\social_user\Form\SocialUserLoginForm;
use Drupal\social_user\Form\SocialUserPasswordForm;

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
  protected function alterRoutes(RouteCollection $collection): void {
    // Set route for the login to point to the SocialUserLoginForm.
    if ($route = $collection->get('user.login')) {
      $route->setDefaults([
        '_form' => SocialUserLoginForm::class,
        '_title' => t('Log in')->render(),
      ]);
    }
    if ($route = $collection->get('user.pass')) {
      $route->setDefaults([
        '_form' => SocialUserPasswordForm::class,
        '_title' => t('Reset your password')->render(),
      ]);
    }
    if ($route = $collection->get('user.reset.form')) {
      $route->setDefault('_title', t('Set your password')->render());
    }
    // Route the user view page to user/{uid}/timeline.
    if ($route = $collection->get('entity.user.canonical')) {
      $route->setPath('/user/{user}/home');
      $defaults = $route->getDefaults();
      $defaults['_title_callback'] = '\Drupal\social_user\Controller\SocialUserController::setUserStreamTitle';
      $route->setDefaults($defaults);
    }

    if ($route = $collection->get('entity.user.edit_form')) {
      $route->setOption('_admin_route', FALSE);
    }

    // Routes for which needs to disable access if the user is blocked.
    $disable_access_for = [
      'view.user_information.user_information',
      'view.events.events_overview',
      'view.topics.page_profile',
    ];

    // Add custom access to routes.
    foreach ($disable_access_for as $route_name) {
      if ($route = $collection->get($route_name)) {
        $route->setRequirement('_custom_access', '\Drupal\social_user\Controller\SocialUserController::accessUsersPages');
      }
    }
  }

}

<?php

namespace Drupal\social_auth_apple_extra\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\social_auth_apple_extra\Controller\AppleAuthExtraController;
use Drupal\social_auth_apple_extra\Form\SocialAuthAppleExtraSettingsForm;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\social_auth_apple_extra\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('social_auth_apple.callback')) {
      $route->setDefault('_controller', AppleAuthExtraController::class . '::callback');
    }

    if ($route = $collection->get('social_auth_apple.settings_form')) {
      $route
        ->setDefault('_form', SocialAuthAppleExtraSettingsForm::class)
        ->setDefault('_title', 'Social Auth Apple settings');
    }
  }

}

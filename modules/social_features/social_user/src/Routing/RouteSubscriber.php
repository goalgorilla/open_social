<?php

namespace Drupal\social_user\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\social_user\Routing
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  protected $configFactory;

  /**
   * RouteSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config object.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->SocialUserSettings = $config_factory->get('social_user.settings');
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Set route for the login to point to the SocialUserLoginForm.
    if ($route = $collection->get('user.login')) {
      $route->setDefaults([
        '_form' => '\Drupal\social_user\Form\SocialUserLoginForm',
        '_title' => t('Log in')->render(),
      ]);
    }
    if ($route = $collection->get('user.pass')) {
      $route->setDefaults([
        '_form' => '\Drupal\social_user\Form\SocialUserPasswordForm',
        '_title' => t('Reset your password')->render(),
      ]);
    }
    if ($route = $collection->get('user.reset.form')) {
      $route->setDefault('_title', t('Set your password')->render());
    }
    // Route the user view page to user/{uid}/timeline.
    if ($route = $collection->get('entity.user.canonical')) {
      $profile_landingpage = $this->SocialUserSettings->get('social_user_profile_landingpage');
      $route->setPath('/user/{user}/' . $profile_landingpage);
      $defaults = $route->getDefaults();
      $defaults['_title_callback'] = '\Drupal\social_user\Controller\SocialUserController::setUserStreamTitle';
      $route->setDefaults($defaults);
    }

    if ($route = $collection->get('entity.user.edit_form')) {
      $route->setOption('_admin_route', FALSE);
    }

  }

}

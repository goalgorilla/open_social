<?php

namespace Drupal\social_auth_apple_extra\Controller;

use Drupal\social_auth_apple\Controller\AppleAuthController as AppleAuthControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class AppleAuthExtraController.
 *
 * @package Drupal\social_auth_apple_extra\Controller
 */
class AppleAuthExtraController extends AppleAuthControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('plugin.network.manager'),
      $container->get('social_auth_apple_extra.user_authenticator'),
      $container->get('social_auth_apple.manager'),
      $container->get('request_stack'),
      $container->get('social_auth.data_handler'),
      $container->get('renderer')
    );
  }

  /**
   * Redirect to provider and back to the registration page on fail.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function userRegister() {
    $response = $this->redirectToProvider();

    if ($response instanceof RedirectResponse) {
      return $this->redirect('user.register');
    }

    return $response;
  }

}

<?php

namespace Drupal\social_auth_apple_extra\Controller;

use Drupal\social_auth_apple\Controller\AppleAuthController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the main controller of the module.
 *
 * @package Drupal\social_auth_apple_extra\Controller
 */
class AppleAuthExtraController extends AppleAuthController {

  /**
   * The redirect route overriding option.
   *
   * TRUE if route name in redirecting method should be replaced by a route to a
   * sign-up page.
   *
   * @var bool
   */
  protected $isSignUp = FALSE;

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
    return $this->userRegisterAction('redirectToProvider');
  }

  /**
   * Registers the new account after redirect from Apple.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function userRegisterCallback() {
    return $this->userRegisterAction('callback');
  }

  /**
   * Provides action for all routes based on the sign-up route.
   *
   * @param string $method
   *   The method name.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  protected function userRegisterAction($method) {
    drupal_static(
      'social_auth_apple_extra',
      'social_auth_apple_extra.user_register_callback'
    );

    $this->isSignUp = TRUE;

    return $this->$method();
  }

  /**
   * Response for path 'social-api/link/*'.
   *
   * Redirects the user to Apple for joining accounts.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function linkAccount() {
    drupal_static(
      'social_auth_apple_extra',
      'social_auth_apple_extra.user_link_callback'
    );

    return $this->redirectToProvider();
  }

  /**
   * Makes joining between account on this site and account on social network.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function linkAccountCallback() {
    drupal_static(
      'social_auth_apple_extra',
      'social_auth_apple_extra.user_link_callback'
    );

    $response = $this->callback();

    if ($this->currentUser()->isAuthenticated()) {
      $this->messenger()->addStatus($this->t(
        'You are now able to log in with @network',
        ['@network' => 'Apple']
      ));

      return $this->redirect('entity.user.edit_form', [
        'user' => $this->currentUser()->id(),
      ]);
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  protected function redirect($route_name, array $route_parameters = [], array $options = [], $status = 302) {
    if ($this->isSignUp) {
      $route_name = 'user.register';
    }

    return parent::redirect($route_name, $route_parameters, $options, $status);
  }

}

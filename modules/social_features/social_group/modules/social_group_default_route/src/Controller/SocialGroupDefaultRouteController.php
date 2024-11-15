<?php

namespace Drupal\social_group_default_route\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\social_group\SocialGroupInterface;
use Drupal\social_group_default_route\SocialGroupDefaultRouteRedirectService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for Social Group Default Routes.
 */
class SocialGroupDefaultRouteController extends ControllerBase {

  /**
   * SocialGroupDefaultRouteController constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\social_group_default_route\SocialGroupDefaultRouteRedirectService $redirectService
   *   The redirect service.
   */
  public function __construct(
    AccountInterface $current_user,
    protected SocialGroupDefaultRouteRedirectService $redirectService,
  ) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('current_user'),
      $container->get('social_group_default_route.redirect_service'),
    );
  }

  /**
   * Redirect user to home page depends on membership.
   *
   * @param \Drupal\social_group\SocialGroupInterface $group
   *   The group object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function groupDefaultRoute(SocialGroupInterface $group): RedirectResponse {
    // The members and non-members should be redirected to their default routes.
    $default_route = $group->hasMember($this->currentUser) ?
      $this->redirectService->getDefaultMemberRoute($group) :
      $this->redirectService->getDefaultNonMemberRoute($group);

    $url = Url::fromRoute($default_route, ['group' => $group->id()]);

    return new RedirectResponse($url->toString());
  }

}

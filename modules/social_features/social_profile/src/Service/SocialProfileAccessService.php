<?php

namespace Drupal\social_profile\Service;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provide a service for Profile Access service.
 *
 * @package Drupal\social_profile\Service
 */
class SocialProfileAccessService {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * SocialProfileAccessService constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * Validation permission for profile pages.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Logged user.
   *
   * @return bool
   *   Return TRUE when user has access.
   */
  public function access(AccountInterface $account): bool {
    $user = $this->routeMatch->getRawParameter('user');
    if ($account->id() == $user) {
      return $account->hasPermission('view own profile profile');
    }

    return $account->hasPermission('view any profile profile');
  }

}

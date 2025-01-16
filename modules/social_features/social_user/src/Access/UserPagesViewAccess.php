<?php

declare(strict_types=1);

namespace Drupal\social_user\Access;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\social_user\Controller\SocialUserController;
use Symfony\Component\Routing\Route;

/**
 * Provides an access checker for user pages.
 */
class UserPagesViewAccess implements AccessInterface {

  /**
   * Constructs a new UserPagesViewAccess instance.
   *
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $classResolver
   *   The class resolver service.
   */
  public function __construct(
    protected ClassResolverInterface $classResolver,
  ) {}

  /**
   * Determines access for the given route and user account.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route object for which access is being checked.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Provides the route match for the given route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which access is being checked.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object indicating whether access is allowed or denied.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account): AccessResultInterface {
    return $this->classResolver
      ->getInstanceFromDefinition(SocialUserController::class)
      ->accessUsersPages($account, $route_match);
  }

}

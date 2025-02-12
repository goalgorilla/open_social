<?php

namespace Drupal\social_language;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Class Access.
 *
 * @package Drupal\social_language
 */
class Access implements AccessInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a Access object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * Checks access to the overview based on permissions and translatability.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route_match to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   * @param string $permission
   *   (optional) The permission.
   *
   * @codingStandardsIgnoreStart until https://www.drupal.org/project/coder/issues/3013953 is fixed
   * @return \Drupal\Core\Access\AccessResult
   *   Whether to grant or deny access.
   * @codingStandardsIgnoreEnd
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account, $permission = NULL) {
    if (count($this->languageManager->getLanguages()) > 1) {
      // If the permission is null the service was not called,
      // and we should get it from the route object.
      if (is_null($permission)) {
        // Get route object.
        $route = $route_match->getRouteObject();
        assert($route instanceof Route);

        // Get defined permission on _social_language_access.
        $permission = $route->getRequirements()['_social_language_access'];
      }

      if (is_string($permission)) {
        return AccessResult::allowedIfHasPermission($account, $permission);
      }

      return AccessResult::neutral();
    }

    return AccessResult::forbidden();
  }

}

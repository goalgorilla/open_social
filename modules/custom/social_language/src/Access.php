<?php

namespace Drupal\social_language;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

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
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account, $permission = NULL) {
    if (count($this->languageManager->getLanguages()) > 1) {
      if (!empty($permission)) {
        return AccessResult::allowedIfHasPermission($account, $permission);
      }
      else {
        return AccessResult::allowed();
      }
    }

    return AccessResult::forbidden();
  }

}

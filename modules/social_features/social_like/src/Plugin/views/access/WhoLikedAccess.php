<?php

namespace Drupal\social_like\Plugin\views\access;

use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\Routing\Route;

/**
 * Manage by organizers only access plugin.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "who_liked_access",
 *   title = @Translation("Who liked this entity"),
 *   help = @Translation("Access to the who liked page.")
 * )
 */
class WhoLikedAccess extends AccessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $account->isAuthenticated();
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route): void {
    $route->setRequirement('_custom_access', '\Drupal\social_like\Controller\WhoLikedController::access');
  }

}

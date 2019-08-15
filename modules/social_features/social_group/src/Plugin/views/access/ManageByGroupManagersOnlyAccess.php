<?php

namespace Drupal\social_group\Plugin\views\access;

use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\Routing\Route;

/**
 * Manage by Group Managers only access plugin.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "group_managers_only",
 *   title = @Translation("Group Managers Only"),
 *   help = @Translation("Access for group managers only.")
 * )
 */
class ManageByGroupManagersOnlyAccess extends AccessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    // Allow here for at least LU, real access check in alterRouteDefinition().
    return $account->isAuthenticated();
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_custom_access', '\Drupal\social_group\Controller\GroupManagersController::access');
  }

}

<?php

namespace Drupal\social_event_managers\Plugin\views\access;

use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\Routing\Route;

/**
 * Manage by organizers only access plugin.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "manage_by_organizers_only",
 *   title = @Translation("Manage by organizers only"),
 *   help = @Translation("Access to the event manage all enrollment page.")
 * )
 */
class ManageByOrganizersOnlyAccess extends AccessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $account->isAuthenticated();
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_custom_access', '\Drupal\social_event_managers\Controller\EventManagersController::access');
  }

}

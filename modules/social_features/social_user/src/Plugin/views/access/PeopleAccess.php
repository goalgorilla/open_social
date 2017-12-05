<?php

namespace Drupal\social_user\Plugin\views\access;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Drupal\views\Plugin\views\access\AccessPluginBase;

/**
 * People page access plugin that provides access control based on some perms.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "people_access",
 *   title = @Translation("People access"),
 *   help = @Translation("Access to the people page.")
 * )
 */
class PeopleAccess extends AccessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return $this->t('Unrestricted');
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    // Check if user has administer users or view user access.
    $administerUsers = $account->hasPermission('administer users');
    $viewUsers = $account->hasPermission('view users');
    return $administerUsers | $viewUsers;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_custom_access', '\Drupal\social_user\Controller\SocialUserController::access');
  }

}

<?php

namespace Drupal\social_user\Plugin\views\access;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Drupal\views\Plugin\views\access\AccessPluginBase;

/**
 * People page access plugin that provides access control for admin overviews.
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
    return $account->hasPermission("access administration pages")
      && $account->hasPermission("list user");
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_permission', 'access administration pages,list user');
  }

}

<?php

namespace Drupal\social_event_an_enroll\Plugin\views\access;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Drupal\views\Plugin\views\access\AccessPluginBase;

/**
 * Manage enrollment page access plugin.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "manage_enrollment_access",
 *   title = @Translation("Manage enrollment access"),
 *   help = @Translation("Access to the event manage enrollment page.")
 * )
 */
class ManageEnrollmentAccess extends AccessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function summaryTitle(): \Drupal\Core\StringTranslation\TranslatableMarkup {
    return $this->t('Unrestricted');
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account): bool {
    // Allow here, since real access is checked in alterRouteDefinition().
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route): void {
    $route->setRequirement('_custom_access', '\Drupal\social_event_an_enroll\Controller\EventAnEnrollController::enrollManageAccess');
  }

}

<?php

namespace Drupal\social_management_overview\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\social_management_overview\Plugin\SocialManagementOverviewGroupManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides management overview page.
 */
class SocialManagementOverview extends ControllerBase {

  /**
   * The overview group manager service.
   *
   * @var \Drupal\social_management_overview\Plugin\SocialManagementOverviewGroupManager
   */
  protected $overviewGroupManager;

  /**
   * Constructs a new SocialManagementOverview object.
   *
   * @param \Drupal\social_management_overview\Plugin\SocialManagementOverviewGroupManager $overview_group_manager
   *   The overview group manager service.
   */
  public function __construct(SocialManagementOverviewGroupManager $overview_group_manager) {
    $this->overviewGroupManager = $overview_group_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.social_management_overview_group')
    );
  }

  /**
   * Returns content of management overview page.
   *
   * @return array
   *   Render array.
   */
  public function content(): array {
    return $this->overviewGroupManager->renderGroups();
  }

}

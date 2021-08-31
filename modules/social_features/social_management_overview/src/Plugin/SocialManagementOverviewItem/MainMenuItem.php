<?php

namespace Drupal\social_management_overview\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Main menu".
 *
 * @SocialManagementOverviewItem(
 *   id = "main_menu_item",
 *   label = @Translation("Main menu"),
 *   description = @Translation("Change or add links to the Main menu."),
 *   weight = 0,
 *   group = "menu_management_group",
 *   route = "entity.menu.edit_form"
 * )
 */
class MainMenuItem extends SocialManagementOverviewItemBase {

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(): array {
    return [
      'menu' => 'main',
    ];
  }

}

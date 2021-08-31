<?php

namespace Drupal\social_management_overview\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Footer menu".
 *
 * @SocialManagementOverviewItem(
 *   id = "footer_menu_item",
 *   label = @Translation("Footer menu"),
 *   description = @Translation("Change or add links to the Footer menu."),
 *   weight = 1,
 *   group = "menu_management_group",
 *   route = "entity.menu.edit_form"
 * )
 */
class FooterMenuItem extends SocialManagementOverviewItemBase {

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(): array {
    return [
      'menu' => 'footer',
    ];
  }

}

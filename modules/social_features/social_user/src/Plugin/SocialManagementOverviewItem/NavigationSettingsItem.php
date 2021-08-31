<?php

namespace Drupal\social_user\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Navigation settings".
 *
 * @SocialManagementOverviewItem(
 *   id = "navigation_settings_item",
 *   label = @Translation("Navigation settings"),
 *   description = @Translation("Select which icons to show or hide in the main (top) navigation bar."),
 *   weight = 3,
 *   group = "menu_management_group",
 *   route = "social_user.navigation_settings"
 * )
 */
class NavigationSettingsItem extends SocialManagementOverviewItemBase {

}

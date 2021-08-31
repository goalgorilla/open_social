<?php

namespace Drupal\social_management_overview\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Regional settings".
 *
 * @SocialManagementOverviewItem(
 *   id = "regional_settings_item",
 *   label = @Translation("Regional settings"),
 *   description = @Translation("Configure the locale and timezone settings."),
 *   weight = 3,
 *   group = "configuration_group",
 *   route = "system.regional_settings"
 * )
 */
class RegionalSettingsItem extends SocialManagementOverviewItemBase {

}

<?php

namespace Drupal\social_profile\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Profile settings".
 *
 * @SocialManagementOverviewItem(
 *   id = "social_profile_settings_item",
 *   label = @Translation("Profile settings"),
 *   weight = 5,
 *   group = "configuration_group",
 *   route = "social_profile.settings"
 * )
 */
class SocialProfileSettingsItem extends SocialManagementOverviewItemBase {

}

<?php

namespace Drupal\social_group\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Group settings".
 *
 * @SocialManagementOverviewItem(
 *   id = "social_group_settings_item",
 *   label = @Translation("Group settings"),
 *   weight = 6,
 *   group = "configuration_group",
 *   route = "social_group.settings"
 * )
 */
class SocialGroupSettingsItem extends SocialManagementOverviewItemBase {

}

<?php

namespace Drupal\social_group_quickjoin\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Group quick join settings".
 *
 * @SocialManagementOverviewItem(
 *   id = "group_quick_join_settings_item",
 *   label = @Translation("Group quick join settings"),
 *   weight = 7,
 *   group = "configuration_group",
 *   route = "social_group_quickjoin.settings"
 * )
 */
class GroupQuickJoinSettingsItem extends SocialManagementOverviewItemBase {

}

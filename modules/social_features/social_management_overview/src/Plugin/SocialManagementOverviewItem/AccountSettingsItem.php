<?php

namespace Drupal\social_management_overview\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Account settings".
 *
 * @SocialManagementOverviewItem(
 *   id = "account_settings_item",
 *   label = @Translation("Account settings"),
 *   description = @Translation("Who can register on your platform and adjust the contents of automatic emails sent by your platform."),
 *   weight = 0,
 *   group = "people_group",
 *   route = "entity.user.admin_form"
 * )
 */
class AccountSettingsItem extends SocialManagementOverviewItemBase {

}

<?php

namespace Drupal\social_user\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Default user profile page".
 *
 * @SocialManagementOverviewItem(
 *   id = "user_settings_item",
 *   label = @Translation("Default user profile page"),
 *   description = @Translation("Choose a page that users will land on when visiting another users' profile (e.g., stream or about page)."),
 *   weight = 4,
 *   group = "people_group",
 *   route = "social_user.settings"
 * )
 */
class UserSettingsItem extends SocialManagementOverviewItemBase {

}

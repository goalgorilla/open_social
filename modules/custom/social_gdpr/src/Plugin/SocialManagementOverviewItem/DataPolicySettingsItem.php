<?php

namespace Drupal\social_gdpr\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Data policy settings".
 *
 * @SocialManagementOverviewItem(
 *   id = "data_policy_settings_item",
 *   label = @Translation("Data policy settings"),
 *   description = @Translation("Administer data policy settings."),
 *   weight = 1,
 *   group = "data_group",
 *   route = "data_policy.data_policy.settings"
 * )
 */
class DataPolicySettingsItem extends SocialManagementOverviewItemBase {

}

<?php

namespace Drupal\social_tagging\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Social tag settings".
 *
 * @SocialManagementOverviewItem(
 *   id = "social_tagging_settings_item",
 *   label = @Translation("Social tag settings"),
 *   description = @Translation("Determine how and if users can use tags."),
 *   weight = 16,
 *   group = "configuration_group",
 *   route = "social_tagging.settings"
 * )
 */
class SocialTaggingSettingsItem extends SocialManagementOverviewItemBase {

}

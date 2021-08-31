<?php

namespace Drupal\social_content_report\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Content reporting settings".
 *
 * @SocialManagementOverviewItem(
 *   id = "social_content_report_settings_item",
 *   label = @Translation("Content reporting settings"),
 *   weight = 17,
 *   group = "configuration_group",
 *   route = "social_content_report.settings"
 * )
 */
class SocialContentReportSettingsItem extends SocialManagementOverviewItemBase {

}

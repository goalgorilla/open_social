<?php

namespace Drupal\social_event_addtocal\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Add to calendar settings".
 *
 * @SocialManagementOverviewItem(
 *   id = "social_event_add_to_cal_settings_item",
 *   label = @Translation("Add to calendar settings"),
 *   description = @Translation("Configure if users can add events to calendars like Google, Yahoo, Outlook etc."),
 *   weight = 9,
 *   group = "configuration_group",
 *   route = "social_event_addtocal.settings"
 * )
 */
class SocialEventAddToCalSettingsItem extends SocialManagementOverviewItemBase {

}

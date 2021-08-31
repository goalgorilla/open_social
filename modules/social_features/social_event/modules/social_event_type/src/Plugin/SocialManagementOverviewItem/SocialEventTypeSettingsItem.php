<?php

namespace Drupal\social_event_type\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Event type settings".
 *
 * @SocialManagementOverviewItem(
 *   id = "social_event_type_settings_item",
 *   label = @Translation("Event type settings"),
 *   description = @Translation("Change requirement and widget settings for event types."),
 *   weight = 12,
 *   group = "configuration_group",
 *   route = "social_event_type.settings"
 * )
 */
class SocialEventTypeSettingsItem extends SocialManagementOverviewItemBase {

}

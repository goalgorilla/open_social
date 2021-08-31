<?php

namespace Drupal\social_event\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Event enrollment settings".
 *
 * @SocialManagementOverviewItem(
 *   id = "social_event_settings_item",
 *   label = @Translation("Event enrollment settings"),
 *   description = @Translation("Configure if users can join a group by attending an event from that group."),
 *   weight = 8,
 *   group = "configuration_group",
 *   route = "social_event.settings"
 * )
 */
class SocialEventSettingsItem extends SocialManagementOverviewItemBase {

}

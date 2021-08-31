<?php

namespace Drupal\social_event_managers\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Event organiser settings".
 *
 * @SocialManagementOverviewItem(
 *   id = "social_event_organiser_settings_item",
 *   label = @Translation("Event organiser settings"),
 *   description = @Translation("Change event organiser settings."),
 *   weight = 13,
 *   group = "configuration_group",
 *   route = "social_event_managers.settings"
 * )
 */
class SocialEventOrganiserSettingsItem extends SocialManagementOverviewItemBase {

}

<?php

namespace Drupal\social_event_max_enroll\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Maximum event enrollment settings".
 *
 * @SocialManagementOverviewItem(
 *   id = "social_event_max_enroll_settings_item",
 *   label = @Translation("Maximum event enrollment settings"),
 *   description = @Translation("Configure if event organisers are allowed to set maximum amount of enrollments to event."),
 *   weight = 11,
 *   group = "configuration_group",
 *   route = "social_event_max_enroll.settings"
 * )
 */
class SocialEventMaxEnrollSettingsItem extends SocialManagementOverviewItemBase {

}

<?php

namespace Drupal\social_event_an_enroll\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Anonymous event enrollment settings".
 *
 * @SocialManagementOverviewItem(
 *   id = "social_event_an_enroll_settings_item",
 *   label = @Translation("Anonymous event enrollment settings"),
 *   description = @Translation("Configure if anonymous visitors can enroll to an event."),
 *   weight = 10,
 *   group = "configuration_group",
 *   route = "social_event_an_enroll.settings"
 * )
 */
class SocialEventAnEnrollSettingsItem extends SocialManagementOverviewItemBase {

}

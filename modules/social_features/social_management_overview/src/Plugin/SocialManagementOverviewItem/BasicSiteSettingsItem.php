<?php

namespace Drupal\social_management_overview\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Basic site settings".
 *
 * @SocialManagementOverviewItem(
 *   id = "basic_site_settings_item",
 *   label = @Translation("Basic site settings"),
 *   description = @Translation("Change the name and email address of your site."),
 *   weight = 0,
 *   group = "configuration_group",
 *   route = "system.site_information_settings"
 * )
 */
class BasicSiteSettingsItem extends SocialManagementOverviewItemBase {

}

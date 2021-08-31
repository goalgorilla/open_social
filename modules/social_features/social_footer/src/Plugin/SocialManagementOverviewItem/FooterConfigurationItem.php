<?php

namespace Drupal\social_footer\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Footer branding configuration".
 *
 * @SocialManagementOverviewItem(
 *   id = "footer_configuration_item",
 *   label = @Translation("Footer branding configuration"),
 *   description = @Translation("Configure the footer branding block."),
 *   weight = 2,
 *   group = "menu_management_group",
 *   route = "social_footer.settings"
 * )
 */
class FooterConfigurationItem extends SocialManagementOverviewItemBase {

}

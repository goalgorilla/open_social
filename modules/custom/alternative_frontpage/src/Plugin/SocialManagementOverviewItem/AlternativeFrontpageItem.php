<?php

namespace Drupal\alternative_frontpage\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Alternative frontpage settings".
 *
 * @SocialManagementOverviewItem(
 *   id = "alernaive_frontpage_item",
 *   label = @Translation("Alternative frontpage settings"),
 *   description = @Translation("Choose the desired frontpage for the site. You can set an alternative frontpage for logged-in users."),
 *   weight = 1,
 *   group = "configuration_group",
 *   route = "alternative_frontpage.settings"
 * )
 */
class AlternativeFrontpageItem extends SocialManagementOverviewItemBase {

}

<?php

namespace Drupal\social_gdpr\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Data usage explanation".
 *
 * @SocialManagementOverviewItem(
 *   id = "data_usage_item",
 *   label = @Translation("Data usage explanation"),
 *   description = @Translation("Inform users about data collection."),
 *   weight = 0,
 *   group = "data_group",
 *   route = "entity.informblock.collection"
 * )
 */
class DataUsageItem extends SocialManagementOverviewItemBase {

}

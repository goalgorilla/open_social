<?php

namespace Drupal\entity_access_by_field\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Public visibility settings".
 *
 * @SocialManagementOverviewItem(
 *   id = "enity_access_by_field_settings_item",
 *   label = @Translation("Public visibility settings"),
 *   weight = 14,
 *   group = "configuration_group",
 *   route = "entity_access_by_field.settings"
 * )
 */
class EntityAccessByFieldSettingsItem extends SocialManagementOverviewItemBase {

}

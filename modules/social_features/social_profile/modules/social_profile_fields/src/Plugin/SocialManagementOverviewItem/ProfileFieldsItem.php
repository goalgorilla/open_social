<?php

namespace Drupal\social_profile_fields\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Profile fields".
 *
 * @SocialManagementOverviewItem(
 *   id = "profile_fields_item",
 *   label = @Translation("Profile fields"),
 *   description = @Translation("Administer which profile fields are enabled."),
 *   weight = 3,
 *   group = "people_group",
 *   route = "social_profile_fields.settings"
 * )
 */
class ProfileFieldsItem extends SocialManagementOverviewItemBase {

}

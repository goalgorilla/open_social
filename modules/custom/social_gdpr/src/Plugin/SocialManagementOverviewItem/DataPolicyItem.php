<?php

namespace Drupal\social_gdpr\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Data policy".
 *
 * @SocialManagementOverviewItem(
 *   id = "data_policy_item",
 *   label = @Translation("Data policy"),
 *   description = @Translation("Manage data policy entities and settings."),
 *   weight = 5,
 *   group = "people_group",
 *   route = "entity.data_policy.collection"
 * )
 */
class DataPolicyItem extends SocialManagementOverviewItemBase {

}

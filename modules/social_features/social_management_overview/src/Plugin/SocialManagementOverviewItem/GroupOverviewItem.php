<?php

namespace Drupal\social_management_overview\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Group overview".
 *
 * @SocialManagementOverviewItem(
 *   id = "group_overview_item",
 *   label = @Translation("Group overview"),
 *   description = @Translation("Overview of all the groups on the platform."),
 *   weight = 4,
 *   group = "content_management_group",
 *   route = "entity.group.collection"
 * )
 */
class GroupOverviewItem extends SocialManagementOverviewItemBase {

}

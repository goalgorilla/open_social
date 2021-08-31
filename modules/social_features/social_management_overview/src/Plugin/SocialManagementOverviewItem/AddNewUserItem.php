<?php

namespace Drupal\social_management_overview\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Add new user".
 *
 * @SocialManagementOverviewItem(
 *   id = "add_new_user_item",
 *   label = @Translation("Add new user"),
 *   description = @Translation("Add new user."),
 *   weight = 2,
 *   group = "people_group",
 *   route = "node.add_page"
 * )
 */
class AddNewUserItem extends SocialManagementOverviewItemBase {

}

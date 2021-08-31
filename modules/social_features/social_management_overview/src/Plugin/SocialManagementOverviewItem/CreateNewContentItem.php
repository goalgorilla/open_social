<?php

namespace Drupal\social_management_overview\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Create new content".
 *
 * @SocialManagementOverviewItem(
 *   id = "create_new_content_item",
 *   label = @Translation("Create new content"),
 *   description = @Translation("Create new content."),
 *   weight = 1,
 *   group = "content_management_group",
 *   route = "node.add_page"
 * )
 */
class CreateNewContentItem extends SocialManagementOverviewItemBase {

}

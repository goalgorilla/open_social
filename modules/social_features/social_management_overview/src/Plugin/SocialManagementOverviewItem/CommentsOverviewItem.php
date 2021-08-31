<?php

namespace Drupal\social_management_overview\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Comments overview".
 *
 * @SocialManagementOverviewItem(
 *   id = "comments_overview_item",
 *   label = @Translation("Comments overview"),
 *   description = @Translation("Overview of all the comments on the platform."),
 *   weight = 2,
 *   group = "content_management_group",
 *   route = "comment.admin"
 * )
 */
class CommentsOverviewItem extends SocialManagementOverviewItemBase {

}

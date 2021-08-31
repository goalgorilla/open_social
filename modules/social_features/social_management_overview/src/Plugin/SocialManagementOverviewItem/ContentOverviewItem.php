<?php

namespace Drupal\social_management_overview\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Content overview".
 *
 * @SocialManagementOverviewItem(
 *   id = "content_overview_item",
 *   label = @Translation("Content overview"),
 *   description = @Translation("Overview of all the content on the platform."),
 *   weight = 0,
 *   group = "content_management_group",
 *   route = "view.content.page_1"
 * )
 */
class ContentOverviewItem extends SocialManagementOverviewItemBase {

}

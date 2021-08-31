<?php

namespace Drupal\social_content_report\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Reported content overview".
 *
 * @SocialManagementOverviewItem(
 *   id = "reported_content_overview_item",
 *   label = @Translation("Reported content overview"),
 *   description = @Translation("Overview of all the reported content."),
 *   weight = 3,
 *   group = "content_management_group",
 *   route = "view.report_overview.overview"
 * )
 */
class ReportedContentOverviewItem extends SocialManagementOverviewItemBase {

}

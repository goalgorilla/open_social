<?php

namespace Drupal\social_management_overview\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Date and time formats".
 *
 * @SocialManagementOverviewItem(
 *   id = "date_time_format_item",
 *   label = @Translation("Date and time formats"),
 *   description = @Translation("Configure how dates and times are displayed."),
 *   weight = 4,
 *   group = "configuration_group",
 *   route = "entity.date_format.collection"
 * )
 */
class DateTimeFormatItem extends SocialManagementOverviewItemBase {

}

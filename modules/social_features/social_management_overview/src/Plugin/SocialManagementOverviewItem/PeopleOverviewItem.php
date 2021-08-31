<?php

namespace Drupal\social_management_overview\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "People overview".
 *
 * @SocialManagementOverviewItem(
 *   id = "people_overview_item",
 *   label = @Translation("People overview"),
 *   description = @Translation("Administration of all the people registered on the site."),
 *   weight = 1,
 *   group = "people_group",
 *   route = "entity.user.collection"
 * )
 */
class PeopleOverviewItem extends SocialManagementOverviewItemBase {

}

<?php

namespace Drupal\social_management_overview\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Taxonomy settings".
 *
 * @SocialManagementOverviewItem(
 *   id = "taxonomy_settings_item",
 *   label = @Translation("Taxonomy settings"),
 *   description = @Translation("Topic types, Event types, Profile tags, Organization tags, Expertise and Interests can be added, removed or changed here."),
 *   weight = 2,
 *   group = "configuration_group",
 *   route = "entity.taxonomy_vocabulary.collection"
 * )
 */
class TaxonomySettingsItem extends SocialManagementOverviewItemBase {

}

<?php

namespace Drupal\social_gdpr\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "User consent overview".
 *
 * @SocialManagementOverviewItem(
 *   id = "user_consent_overview_item",
 *   label = @Translation("User consent overview"),
 *   description = @Translation("View user consents per data policy."),
 *   weight = 2,
 *   group = "data_group",
 *   route = "view.data_policy_agreements.page"
 * )
 */
class UserConsentOverviewItem extends SocialManagementOverviewItemBase {

}

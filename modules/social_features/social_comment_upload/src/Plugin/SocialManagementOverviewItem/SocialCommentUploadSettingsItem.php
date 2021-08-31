<?php

namespace Drupal\social_comment_upload\Plugin\SocialManagementOverviewItem;

use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;

/**
 * Provides a new overview item "Social comment upload settings".
 *
 * @SocialManagementOverviewItem(
 *   id = "social_comment_upload_settings_item",
 *   label = @Translation("Social comment upload settings"),
 *   description = @Translation("Configure if people can attach files to comments."),
 *   weight = 15,
 *   group = "configuration_group",
 *   route = "social_comment_upload.settings"
 * )
 */
class SocialCommentUploadSettingsItem extends SocialManagementOverviewItemBase {

}

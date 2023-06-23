<?php

/**
 * @file
 * Contains post update hook implementations.
 */

use Drupal\message\Entity\MessageTemplate;

/**
 * Update follow tag notifications to use the new condition.
 */
function social_follow_tag_post_update_notification_efficiency(): void {
  MessageTemplate::load('update_node_following_tag')
    ?->setThirdPartySetting("activity_logger", "activity_entity_condition", "content_tags_updated")
      ->save();
}

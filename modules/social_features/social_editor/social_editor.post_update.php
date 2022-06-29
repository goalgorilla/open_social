<?php

/**
 * @file
 * Contains post-update hooks for the Social Editor module.
 */

use Drupal\user\RoleInterface;

/**
 * Grant authenticated user permission to use Simple HTML format.
 */
function social_core_post_update_11401(): void {
  user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, ['use text format simple_html']);
}

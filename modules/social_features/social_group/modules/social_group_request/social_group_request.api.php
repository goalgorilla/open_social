<?php

/**
 * @file
 * Hooks provided by the Social Group Request module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide a method to alter array of group types used in social group request.
 *
 * @param array $group_types
 *   List of group types used in open social.
 *
 * @ingroup social_group_api
 */
function hook_social_group_request_alter(array &$group_types) {
  $group_types[] = 'flexible_group';
}

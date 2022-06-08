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
 * @deprecated in social:11.4.0 and is removed from social:12.0.0. Use
 *   hook_social_group_join_method_usage instead.
 *
 * @see https://www.drupal.org/node/3254715
 *
 * @ingroup social_group_api
 */
function hook_social_group_request_alter(array &$group_types) {
  $group_types[] = 'flexible_group';
}

/**
 * @} End of "addtogroup hooks".
 */

<?php

/**
 * @file
 * Hooks provided by the Social Group module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide a method to alter array of group types used in open social.
 *
 * @param array $social_group_types
 *   List of group types used in open social.
 *
 * @ingroup social_group_api
 */
function hook_social_group_types_alter(array &$social_group_types) {
  $social_group_types[] = 'challenge';
}

/**
 * @} End of "addtogroup hooks".
 */

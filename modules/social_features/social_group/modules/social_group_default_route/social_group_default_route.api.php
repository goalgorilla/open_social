<?php

/**
 * @file
 * Hooks provided by the Comment module.
 */

/**
 * @addtogroup hooks
 * @{
 *
 * @param array $types
 *   An array of all available group types.
 */
function hook_social_group_default_route_types_alter(array $types) {
  // Enable functionality for secret groups.
  $types[] = 'my_custom_group';
}

/**
 * @} End of "addtogroup hooks".
 */

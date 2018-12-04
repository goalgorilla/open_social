<?php

/**
 * @file
 * Hooks provided by the Social Follow Content module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide a method to alter the automatically follow content types.
 *
 * @param array $types
 *   An array of content types.
 */
function hook_social_follow_content_types_alter(array &$types) {
  $types[] = 'topic';
}

/**
 * @} End of "addtogroup hooks".
 */

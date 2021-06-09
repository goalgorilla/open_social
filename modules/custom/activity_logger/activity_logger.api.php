<?php

/**
 * @file
 * Hooks provided by the Activity Logger module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide a method to alter message templates where duplicates are allowed.
 *
 * @param array $allowed_duplicates
 *   List of message template id.
 *
 * @ingroup activity_logger_api
 */
function hook_activity_allowed_duplicates_alter(array &$allowed_duplicates) {
  $allowed_duplicates[] = 'update_challenge_phase_authors';
}

/**
 * @} End of "addtogroup hooks".
 */

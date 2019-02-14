<?php

/**
 * @file
 * Hooks provided by the Social Event Organisers module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide a method to show some message when action is completed.
 *
 * @param bool $success
 *   Was the process successfull?
 *
 * @return array
 *   An associative array which contains two single-line text values for a
 *   situation when action finish with one result ("single" array key) and many
 *   results ("plural" array key).
 *
 * @ingroup social_event_managers_api
 */
function hook_social_event_managers_action_ACTION_ID_finish($success) {
  if ($success) {
    return [
      'singular' => 'Your email has been sent to 1 selected enrollee successfully',
      'plural' => 'Your email has been sent to @count selected enrollees successfully',
    ];
  }

  return [
    'singular' => 'Your email has not been sent to 1 selected enrollee successfully',
    'plural' => 'Your email has not been sent to @count selected enrollees successfully',
  ];
}

/**
 * @} End of "addtogroup hooks".
 */

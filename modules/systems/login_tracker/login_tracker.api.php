<?php

/**
 * @file
 * Hooks provided by the Login Tracker module.
 */

/**
 * Override whether a login request should be tracked or not.
 *
 * @param bool $track_login
 *   Boolean indicating whether to track the login (true), or not (false).
 * @param object $account
 *   The user object on which the operation is being performed.
 *
 * @see login_tracker_user_login()
 */
function hook_login_tracker_track_login_alter(&$track_login, $account) {
  // We only track login requests on the 1st of the month.
  if (date('d') != 1) {
    return FALSE;
  }
  else {
    return TRUE;
  }
}

/**
 * Supply, or modify custom data stored with the tracked login.
 *
 * @param array $data
 *   Associative array of custom data that will be stored with the login.
 * @param object $account
 *   The user object on which the operation is about to be recorded.
 *
 * @see login_tracker_user_login()
 */
function hook_login_tracker_login_data_alter(array &$data, $account) {
  // Store a random number between 1 and 10 with the tracked login.
  $data['my-random-number'] = rand(1, 10);
}

<?php

/**
 * @file
 * Post update hooks for the Social Profile Privacy module.
 */

/**
 * Re-save the All and Users indices to add restricted name field.
 */
function social_profile_privacy_post_update_8001_restricted_name_field() {
  _social_profile_privacy_resave_search_indexes();
}

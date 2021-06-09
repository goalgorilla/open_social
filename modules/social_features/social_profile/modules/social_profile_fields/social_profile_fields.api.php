<?php

/**
 * @file
 * Hooks provided by the social_profile_fields module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide a method to alter mapping from profile field to export plugin.
 *
 * @param array $mapping
 *   List of export plugins and their corresponding profile fields.
 *
 * @ingroup social_profile_fields_api
 */
function hook_profile_field_export_mapping_alter(array &$mapping) {
  // Replace the default plugin with our own.
  $mapping['custom_user_first_name'] = $mapping['user_first_name'];

  // And unset the default.
  unset($mapping['user_first_name']);

  // Add let's add a new one.
  $mapping['custom_user_field'] = 'profile_profile_field_profile_custom';
}

/**
 * @} End of "addtogroup hooks".
 */

<?php

/**
 * @file
 * Hooks provided by the core_search_facets module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Adds field types as possible options for facets.
 *
 * @param array $allowed_field_types
 *   The field types.
 *
 * @return array
 *   Array that contains the field types.
 */
function hook_facets_core_allowed_field_types(array $allowed_field_types) {
  $allowed_field_types[] = 'float';
  return $allowed_field_types;
}

/**
 * @} End of "addtogroup hooks".
 */

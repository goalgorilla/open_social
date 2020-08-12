<?php

/**
 * @file
 * Hooks provided by the Social Core module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide a method to alter the default filter format for a content type.
 *
 * @param string _alter(
 *   The filter format that is default.
 *
 * @ingroup social_core_api
 */
function hook_social_filter_format_default_alter(&$filter_format) {
  $filter_format = 'full_html';
}

/**
 * @} End of "addtogroup hooks".
 */

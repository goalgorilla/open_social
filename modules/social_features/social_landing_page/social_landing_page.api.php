<?php

/**
 * @file
 * Hooks provided by the Social Landing Page module.
 */

/**
 * @addtogroup hooks
 */

/**
 * Provide a method to alter array of activity overview block items.
 *
 * @param array $build
 *   Array with build data.
 *
 * @ingroup social_landing_page_api
 */
function hook_social_landing_page_activity_overview_alter(array &$build) {
  unset($build[0]['group_info']);
}

/**
 * @} End of "addtogroup hooks".
 */

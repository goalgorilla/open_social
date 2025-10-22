<?php

/**
 * @file
 * Hooks provided by the social_course module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide a method to alter content types which can be added to course section.
 *
 * @param array $content_types
 *   List of machine names of content types.
 *
 * @ingroup social_course_api
 */
function hook_social_course_material_types_alter(array &$content_types) {
  if (in_array('course_video', $content_types) && !in_array('course_audio', $content_types)) {
    $content_types[] = 'course_audio';
  }
}

/**
 * Provide a method to alter node IDs in the course section.
 *
 * @param array $node_ids
 *   List of node IDs of course section.
 *
 * @ingroup social_course_api
 */
function hook_social_course_materials_alter(array &$node_ids) {
  $node_ids = array_diff($node_ids, [5, 7]);
}

/**
 * Provide a method to alter content types than need to be excluded from search.
 *
 * @param array $content_types
 *   List of machine names of content types.
 *
 * @ingroup social_course_api
 */
function hook_social_course_materials_excluded_from_search_alter(array &$content_types) {
  $content_types[] = 'course_article';
}

/**
 * @} End of "addtogroup hooks".
 */

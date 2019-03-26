<?php

/**
 * @file
 * Hooks provided by the social_content_report module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide a list of flag IDs which defined as report type.
 *
 * @return array
 *   The flag entity IDs.
 *
 * @see \Drupal\social_content_report\ContentReportService::getReportFlagTypes()
 */
function hook_social_content_report_flags() {
  return [
    'report_comment',
    'report_node',
    'report_post',
  ];
}

/**
 * Allows a module to alter the report types.
 *
 * @param array $entity_ids
 *   The flag entity IDs.
 *
 * @see \Drupal\social_content_report\ContentReportService::getReportFlagTypes()
 */
function hook_social_content_report_flags_alter(array &$entity_ids) {
  $id = array_search('report_comment', $entity_ids);

  if ($id !== FALSE) {
    unset($entity_ids[$id]);
  }
}

/**
 * @} End of "addtogroup hooks".
 */

<?php

/**
 * @file
 * Contains Views hooks.
 */

/**
 * Implements hook_views_data_alter().
 */
function social_course_statistics_views_data_alter(array &$data): void {
  // Add relationship to course enrollment of a group content.
  $data['group_relationship_field_data']['course_enrollment']['relationship'] = [
    'title' => t('Course enrollment'),
    'label' => t('Course enrollment'),
    'group' => 'Course Enrollment',
    'help' => t('Reference to course enrollment of a group content.'),
    'id' => 'standard',
    'base' => 'course_enrollment',
    'base field' => 'uid',
    'field' => 'entity_id',
    'extra' => [
      [
        'left_field' => 'gid',
        'field' => 'gid',
      ],
    ],
  ];

  // Add relationship to course enrollment of a section.
  $data['node_field_data']['course_enrollment']['relationship'] = [
    'title' => t('Course enrollment'),
    'label' => t('Course enrollment'),
    'group' => 'Course Enrollment',
    'help' => t('Reference to course enrollment of a section.'),
    'id' => 'standard',
    'base' => 'course_enrollment',
    'base field' => 'sid',
    'field' => 'nid',
  ];
}

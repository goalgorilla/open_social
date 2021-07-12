<?php

/**
 * @file
 * Contains Views hooks.
 */

/**
 * Implements hook_views_data_alter().
 */
function social_group_views_data_alter(array &$data) {
  // @todo remove this when this hits https://www.drupal.org/node/2715285.
  // Also note that by default the Group Creator is a Member now, that's
  // where we check on in Drupal\social_group\Plugin\views\argument\UserUid.php.
  $data['groups']['group_membership_or_creator'] = [
    'group' => t('Group Content'),
    'title' => t('User is member'),
    'help' => t('User is member of the group'),
    'argument' => [
      'field' => 'uid',
      'name table' => 'group_content_field_data',
      'name field' => 'name',
      'id' => 'group_content_user_uid',
      'no group by' => TRUE,
    ],
  ];

  // Create our own views VBO field for enrollments.
  $data['views']['social_views_bulk_operations_bulk_form_group'] = [
    'title' => t('Social Views bulk operations for Group Management'),
    'help' => t("Process enrollments returned by the view with Views Bulk Operations actions."),
    'field' => [
      'id' => 'social_views_bulk_operations_bulk_form_group',
    ],
  ];

  // Field with the count of group members.
  $data['group_content']['group_membership_count'] = [
    'title' => t('Membership count'),
    'field' => [
      'title' => t('Membership count'),
      'help' => t('Displays the count of members in a group.'),
      'id' => 'social_group_membership_count',
    ],
  ];
}

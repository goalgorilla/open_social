<?php

/**
 * @file
 * Provide views data that isn't tied to any other module.
 */

/**
 * Implements hook_views_data_alter().
 */
function gvbo_views_data_alter(array &$data) {
  $data['views']['group_views_bulk_operations_bulk_form'] = [
    'title' => t('Group bulk operations for Group Content'),
    'help' => t("Process Group Content returned by the view with Views Bulk Operations' actions."),
    'field' => [
      'id' => 'group_views_bulk_operations_bulk_form',
    ],
  ];
}

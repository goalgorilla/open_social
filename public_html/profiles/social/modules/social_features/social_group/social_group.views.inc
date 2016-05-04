<?php

/**
 * @file
 * Contains Views hooks.
 */

/**
 * Implements hook_views_data_alter().
 */
function social_group_views_data_alter(array &$data) {
  // @TODO remove this when this hits https://www.drupal.org/node/2715285.
  // Also note that by default the Group Creator is a Member now, that's
  // where we check on in Drupal\social_group\Plugin\views\argument\UserUid.php.
  $data['groups']['group_membership_or_creator'] = array(
    'group' => t('Group Content'),
    'title' => t('User is member'),
    'help' => t('User is member of the group'),
    'argument' => array(
      'field' => 'uid',
      'name table' => 'group_content_field_data',
      'name field' => 'name',
      'id' => 'group_content_user_uid',
      'no group by' => TRUE,
    ),
  );
}
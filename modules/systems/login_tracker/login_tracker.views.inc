<?php

/**
 * @file
 * Views integration for the Login Tracker module.
 */

/**
 * Implements hook_views_data().
 */
function login_tracker_views_data() {
  $data = [];
  $data['login_tracker']['table']['group'] = t('User logins');
  $data['login_tracker']['table']['base'] = [
    'field' => 'record_id',
    'title' => t('User logins'),
    'help'  => t('Contains data about user logins.'),
  ];
  $data['login_tracker']['table']['join'] = [
    'users_field_data' => [
      'left_field' => 'uid',
      'field'      => 'uid',
    ],
  ];
  // The UID field.
  $data['login_tracker']['record_id'] = [
    'title' => t('Login record ID'),
    'help'  => t("The unique ID referring to this login record."),
    'field' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'title' => t('Record ID'),
      'id' => 'numeric',
    ],
  ];
  $data['login_tracker']['uid'] = [
    'title' => t('UID'),
    'help'  => t("The user's UID."),
    'field' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'title' => t('UID'),
      'id' => 'numeric',
    ],
    'relationship' => [
      'title'              => t('User who logged in'),
      'help'               => t('The user associated with the login record.'),
      'id'                 => 'standard',
      'base'               => 'users_field_data',
      'base field'         => 'uid',
      'field'              => 'uid',
      'label'              => t('User who logged in'),
    ],
  ];
  // The login timestamp field.
  $data['login_tracker']['login_timestamp'] = [
    'title'  => t('Login timestamp'),
    'help'   => t('The timestamp that the user logged in at.'),
    'field'  => [
      'id' => 'date',
    ],
    'sort'   => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];
  $data['login_tracker']['data'] = [
    'title' => t('Login data'),
    'help'  => t('Login record information.'),
    'field' => [
      'id'             => 'serialized',
      'click sortable' => FALSE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
  ];
  return $data;
}

<?php

/**
 * @file
 * Provide views data for the download_count.module.
 */

/**
 * Implements hook_views_data().
 */
function download_count_views_data() {

  // Base table.
  $data['download_count']['table']['group'] = t('Download history');
  $data['download_count']['table']['base'] = array(
    'field' => 'dcid',
    'title' => t('Download history'),
    'help' => t('Download history of download count module managed file downloads.'),
  );

  // Join for files table.
  $data['download_count']['table']['join']['file_managed'] = array(
    'left_field' => 'fid',
    'field' => 'fid',
  );

  $data['download_count']['dcid'] = array(
    'title' => t('ID'),
    'help' => t('The download count ID of the download.'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
  );

  $data['download_count']['fid'] = array(
    'title' => t('File'),
    'help' => t('The file information of the downloaded file.'),
    'relationship' => array(
      'base' => 'file_managed',
      'base field' => 'fid',
      'label' => t('File'),
    ),
  );

  $data['download_count']['uid'] = array(
    'title' => t('User'),
    'help' => t('The user who downloaded the file.'),
    'relationship' => array(
      'base' => 'users_field_data',
      'base field' => 'uid',
      'label' => t('User'),
    ),
  );

  // Type (entity type).
  $data['download_count']['type'] = array(
    'title' => t('Entity Type'),
    'help' => t('The type of the entity from which the file was downloaded.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),

    'filter' => array(
      'id' => 'standard',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['download_count']['id'] = array(
    'title' => t('Entity ID'),
    'help' => t('The id of the entity from which the file was downloaded.'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
  );

  $data['download_count']['referrer'] = array(
    'title' => t('Referrer'),
    'help' => t('Referrer URI.'),
    'field' => array(
      'id' => 'url',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['download_count']['ip_address'] = array(
    'title' => t('IP Address'),
    'help' => t('IP address of the user who downloaded the file.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['download_count']['timestamp'] = array(
    'title' => t('Date-time Downloaded'),
    'help' => t('The date and time the file was downloaded.'),
    'field' => array(
      'id' => 'date',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date',
    ),
  );

  return $data;
}

/**
 * Implements hook_views_data_alter().
 */
function download_count_views_data_alter(&$data) {
  // Join for files table.
  $data['file_managed']['table']['join']['download_count'] = array(
    'left_field' => 'fid',
    'field' => 'fid',
  );
}

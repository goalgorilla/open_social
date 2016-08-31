<?php

/**
 * @file
 * Rules specific functions for the download_count module.
 */

/**
 * Implements hook_rules_event_info().
 */
function download_count_rules_event_info() {
  return array(
    'download_count_file_download' => array(
      'group' => t('Download Count'),
      'label' => t('A file has been downloaded'),
      'module' => 'download_count',
      'variables' => array(
        'file' => array(
          'type' => 'file',
          'label' => t('Downloaded file'),
        ),
        'user' => array(
          'type' => 'user',
          'label' => t('Downloading user'),
        ),
        'node' => array(
          'type' => 'node',
          'label' => t('Node the file download is associated with'),
        ),
      ),
    ),
  );
}

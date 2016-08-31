<?php

/**
 * @file
 * Drush commands for download_count.
 */

define('SPARKLINE_BASE_URI', 'http://www.omnipotent.net/jquery.sparkline/');
define('SPARKLINE_FILENAME', 'jquery.sparkline.min.js');
define('SPARKLINE_VERSION', '1.6');

/**
 * Implements hook_drush_help().
 */
function download_count_drush_help($section) {
  switch ($section) {
    case 'drush:sparkline-plugin':
      return dt("This command will download the jquery.sparkline plugin from http://www.omnipotent.net. A single argument will be assumed to be the version. In order to specify a path, you must also include the version.");
  }
}

/**
 * Implements hook_drush_command().
 */
function download_count_drush_command() {
  $items['sparkline-plugin'] = array(
    'description' => dt('Download the jquery.sparkline plugin.'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    'callback' => 'download_count_sparkline_download',
    'aliases' => array('sparklines'),
    'arguments' => array(
      'version' => dt('Optional: the version of the sparkline plugin to download (currently the default is ' . SPARKLINE_VERSION . ')'),
      'path' => dt('Optional: a path to install the sparkline plugin. If omitted drush will use /sites/all/libraries/jquery.sparkline.'),
    ),
  );
  return $items;
}

/**
 * Callback to do the actual sparkling plugin download.
 */
function download_count_sparkline_download() {
  $args = func_get_args();

  if (count($args) == 0) {
    $uri = SPARKLINE_BASE_URI . SPARKLINE_VERSION . '/' . SPARKLINE_FILENAME;
    $path = 'sites/all/libraries/jquery.sparkline';
  }
  elseif (count($args) == 1) {
    $uri = SPARKLINE_BASE_URI . $args[0] . '/' . SPARKLINE_FILENAME;
    $path = 'sites/all/libraries/jquery.sparkline';
  }
  elseif (count($args) == 2) {
    $uri = SPARKLINE_BASE_URI . $args[0] . '/' . SPARKLINE_FILENAME;
    $path = $args[1];
    print 'path= ' . $path;
    print is_dir($path);
  }
  else {
    drush_log(dt('Too many arguments. Use "drush help sparklines" to see information for this command'), 'error');
    return;
  }

  if (!is_dir($path)) {
    drush_op('mkdir', $path);
    drush_log(dt('Directory @path was created', array('@path' => $path)), 'notice');
  }

  $original_dir = getcwd();
  chdir($path);

  if (is_file(SPARKLINE_FILENAME)) {
    drush_op('unlink', SPARKLINE_FILENAME);
  }

  if (!drush_shell_exec('wget ' . $uri)) {
    drush_shell_exec('curl -O ' . $uri);
  }

  if (is_file(SPARKLINE_FILENAME)) {
    drush_log(dt('The sparkline plugin has been downloaded to @path', array('@path' => $path)), 'success');
  }
  else {
    drush_log(dt('Drush was unable to download the sparkline plugin to @path', array('@path' => $path)), 'error');
  }

  chdir($original_dir);
}

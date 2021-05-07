<?php

$databases['default']['default'] = [
  'database' => 'tugboat',
  'username' => 'tugboat',
  'password' => 'tugboat',
  'prefix' => '',
  'host' => 'db',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
];

$settings['trusted_host_patterns'] = [
  '^' . getenv('TUGBOAT_DEFAULT_SERVICE_URL_HOST') . '$',
];

// Use the TUGBOAT_REPO_ID to generate a hash salt for Tugboat sites.
$settings['hash_salt'] = hash('sha256', getenv('TUGBOAT_REPO_ID'));

// Use the TUGBOAT_PREVIEW_ID to let Drupal know when to rebuild caches.
$settings['deployment_identifier'] = getenv('TUGBOAT_PREVIEW_ID');

// Send e-mail using Tugboat's SMTP server so they are captured.
$config['swiftmailer.transport']['transport'] = 'smtp';
$config['swiftmailer.transport']['smtp_host'] = getenv('TUGBOAT_SMTP');

// Set a config sync directory to stop Drupal from showing us errors.
$settings['config_sync_directory'] = "/var/www/config/sync";

// Specify where the private files can be stored.
$settings['file_private_path'] = "/var/www/files_private";

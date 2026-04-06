<?php

/**
 * @file
 * Install hooks for signed file upload module.
 */

declare(strict_types=1);

use Drupal\Core\Site\Settings;

/**
 * Implements hook_requirements().
 */
function signed_file_upload_requirements(string $phase) : array {
  $requirements = [];

  if ($phase === 'runtime' || $phase === 'install') {
    $requirements['signed_file_upload_php_architecture'] = [
      'title' => t('PHP Architecture'),
      'value' => PHP_INT_SIZE === 8 ? t('64-bit') : t('32-bit'),
      'severity' => PHP_INT_SIZE === 8 ? REQUIREMENT_OK : REQUIREMENT_ERROR,
      'description' => t('The Signed File Upload module requires 64-bit PHP.'),
    ];

    // Only show the requirement if it's not met since the private file path
    // is already shown as a core requirement when configured.
    $private_path = Settings::get('file_private_path', '');
    if ($private_path === '') {
      $requirements['signed_file_upload_private_filesystem'] = [
        'title' => t('Private file system path'),
        'value' => t('Not set'),
        'severity' => REQUIREMENT_ERROR,
        'description' => t('Signed File Upload requires a private file path so staged uploads can be stored reliably. Set @setting in settings.php.', [
          '@setting' => '$settings[\'file_private_path\']',
        ]),
      ];
    }

  }

  return $requirements;
}

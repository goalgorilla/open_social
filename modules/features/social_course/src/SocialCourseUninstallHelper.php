<?php

declare(strict_types=1);

namespace Drupal\social_course;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;

/**
 * Helper for cleaning up group relationship data on module uninstall.
 */
class SocialCourseUninstallHelper {

  /**
   * Deletes group relationship records for group content types of a module.
   *
   * Scans the module's config/install and config/optional directories for
   * group.content_type.* configs and removes matching records from the
   * group_relationship and group_relationship_field_data tables.
   *
   * @param string $module
   *   The module machine name.
   *
   * @throws \Exception
   */
  public static function deleteGroupRelationships(string $module): void {
    $module_path = \Drupal::service('extension.list.module')->getPath($module);
    $database = \Drupal::database();

    $subdirs = [
      InstallStorage::CONFIG_INSTALL_DIRECTORY,
      InstallStorage::CONFIG_OPTIONAL_DIRECTORY,
    ];

    $types = [];
    foreach ($subdirs as $subdir) {
      $dir = $module_path . '/' . $subdir;
      if (!is_dir($dir)) {
        continue;
      }

      $storage = new FileStorage($dir);
      foreach ($storage->listAll('group.content_type.') as $config_name) {
        $data = $storage->read($config_name);
        if (!empty($data['id'])) {
          $types[] = $data['id'];
        }
      }
    }

    if (empty($types)) {
      return;
    }

    $tables = ['group_relationship', 'group_relationship_field_data'];
    foreach ($tables as $table) {
      if ($database->schema()->tableExists($table)) {
        $database->delete($table)
          ->condition('type', $types, 'IN')
          ->execute();
      }
    }
  }

  /**
   * Adds enforced dependency on a module to all its config.
   *
   * Scans the module's config/install and config/optional directories and
   * adds the module as an enforced dependency to each active config.
   *
   * IMPORTANT: one-time method usage could be deleted easily on code cleanup.
   *
   * @param string $module
   *   The module machine name.
   */
  public static function addEnforcedDependency(string $module): void {
    $module_path = \Drupal::service('extension.list.module')->getPath($module);
    $config_factory = \Drupal::configFactory();

    $subdirs = [
      InstallStorage::CONFIG_INSTALL_DIRECTORY,
      InstallStorage::CONFIG_OPTIONAL_DIRECTORY,
    ];

    foreach ($subdirs as $subdir) {
      $dir = $module_path . '/' . $subdir;
      if (!is_dir($dir)) {
        continue;
      }

      $storage = new FileStorage($dir);

      foreach ($storage->listAll() as $config_name) {
        $config = $config_factory->getEditable($config_name);
        if ($config->isNew()) {
          continue;
        }

        $dependencies = $config->get('dependencies') ?? [];
        $enforced_modules = $dependencies['enforced']['module'] ?? [];
        if (!in_array($module, $enforced_modules)) {
          $enforced_modules[] = $module;
          sort($enforced_modules);
          $dependencies['enforced']['module'] = $enforced_modules;
          $config->set('dependencies', $dependencies);
          $config->save(TRUE);
        }
      }
    }
  }

}
